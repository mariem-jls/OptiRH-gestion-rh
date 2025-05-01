<?php

namespace App\Controller;

use DateTimeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Psr\Log\LoggerInterface;
use DateTime;
use DateTimeZone;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Entity\Project;
use App\Repository\GsProjet\ProjectRepository;

class GoogleCalendarController extends AbstractController
{
    private Client $client;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
        private readonly MailerInterface $mailer,
        private readonly ProjectRepository $projectRepository
    ) {
        $this->client = new Client();
        $this->client->setApplicationName('Optirh');

        // Validate environment variables
        $clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? null;
        $clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? null;
        $redirectUri = $_ENV['GOOGLE_REDIRECT_URI'] ?? null;

        if (!$clientId || !$clientSecret || !$redirectUri) {
            throw new \RuntimeException(
                'Missing Google API credentials. Ensure GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and GOOGLE_REDIRECT_URI are set in .env.'
            );
        }

        $this->client->setClientId($clientId);
        $this->client->setClientSecret($clientSecret);
        $this->client->setRedirectUri($redirectUri);
        $this->client->addScope(Calendar::CALENDAR);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
        $this->client->setIncludeGrantedScopes(true);

        // Log the redirect URI for debugging
        $this->logger->debug('Google Client initialized with redirect URI: ' . $redirectUri);
    }

    #[Route('/connect/google-calendar', name: 'google_calendar_connect')]
    public function connect(): RedirectResponse
    {
        try {
            // Log the redirect URI being used
            $this->logger->debug('Redirect URI for OAuth: ' . $this->client->getRedirectUri());
            $authUrl = $this->client->createAuthUrl();
            if (!$authUrl) {
                throw new \Exception('Failed to create auth URL');
            }
            return $this->redirect($authUrl);
        } catch (\Exception $e) {
            $this->logger->error('Google Calendar connect error: ' . $e->getMessage());
            $this->addFlash('error', 'Erreur lors de la génération de l\'URL d\'authentification: ' . $e->getMessage());
            return $this->redirectToRoute('gs-projet_project_app_calendar');
        }
    }

    #[Route('/google-calendar/callback', name: 'google_calendar_callback')]
    public function callback(Request $request): RedirectResponse
    {
        if ($request->query->has('error')) {
            $error = $request->query->get('error');
            $this->logger->error('Google Calendar auth error: ' . $error);
            $this->addFlash('error', 'Erreur d\'autorisation Google Calendar: ' . $error);
            return $this->redirectToRoute('gs-projet_project_app_calendar');
        }

        try {
            $code = $request->query->get('code');
            if (!$code) {
                throw new \Exception('Authorization code missing');
            }
            $token = $this->client->fetchAccessTokenWithAuthCode($code);
            if (isset($token['error'])) {
                throw new \Exception('Token fetch error: ' . $token['error']);
            }
            $this->getSession()->set('google_calendar_token', $token);
            $this->addFlash('success', 'Connexion réussie à Google Calendar');
        } catch (\Exception $e) {
            $this->logger->error('Google Calendar callback error: ' . $e->getMessage());
            $this->addFlash('error', 'Échec de la connexion à Google Calendar: ' . $e->getMessage());
        }

        return $this->redirectToRoute('gs-projet_project_app_calendar');
    }

    #[Route('/google-calendar/events', name: 'google_calendar_events')]
    public function listEvents(): Response
    {
        if (!$this->getSession()->has('google_calendar_token')) {
            return $this->redirectToRoute('google_calendar_connect');
        }

        try {
            $this->initializeClient();
            $service = new Calendar($this->client);
            
            $events = $service->events->listEvents('primary', [
                'maxResults' => 10,
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => (new DateTime('now', new DateTimeZone('UTC')))->format('c'),
            ]);

            return $this->render('calendar/google_events.html.twig', [
                'events' => $events->getItems(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Google Calendar events error: ' . $e->getMessage());
            $this->addFlash('error', 'Erreur lors de la récupération des événements: ' . $e->getMessage());
            return $this->redirectToRoute('gs-projet_project_app_calendar');
        }
    }

    #[Route('/google-calendar/add-event', name: 'google_calendar_add_event')]
    public function addEvent(Request $request): Response
    {
        if (!$this->getSession()->has('google_calendar_token')) {
            return $this->redirectToRoute('google_calendar_connect');
        }

        try {
            $this->initializeClient();
            
            if ($request->isMethod('POST')) {
                $service = new Calendar($this->client);
                
                $event = new Event([
                    'summary' => $request->request->get('title'),
                    'description' => $request->request->get('description'),
                    'start' => $this->createEventDateTime($request->request->get('start_time')),
                    'end' => $this->createEventDateTime($request->request->get('end_time')),
                ]);

                $service->events->insert('primary', $event);
                $this->addFlash('success', 'Événement ajouté avec succès');
                return $this->redirectToRoute('google_calendar_events');
            }
        } catch (\Exception $e) {
            $this->logger->error('Google Calendar add event error: ' . $e->getMessage());
            $this->addFlash('error', 'Erreur lors de l\'ajout de l\'événement: ' . $e->getMessage());
        }

        return $this->render('calendar/add_event.html.twig');
    }

    #[Route('/google-calendar/create-meet/{projectId}', name: 'google_calendar_create_meet')]
    public function createMeet(int $projectId): RedirectResponse
    {
        if (!$this->getSession()->has('google_calendar_token')) {
            $this->addFlash('error', 'Veuillez connecter votre compte Google Calendar');
            return $this->redirectToRoute('google_calendar_connect');
        }

        try {
            $this->initializeClient();
            $service = new Calendar($this->client);

            // Fetch project using ProjectRepository
            $project = $this->projectRepository->find($projectId);
            if (!$project) {
                $this->addFlash('error', 'Projet non trouvé');
                return $this->redirectToRoute('gs-projet_project_show', ['id' => $projectId]);
            }

            // Create Google Meet event
            $startTime = new DateTime('+1 hour'); // Schedule 1 hour from now
            $endTime = (clone $startTime)->modify('+1 hour');

            $event = new Event([
                'summary' => 'Réunion pour le projet: ' . $project->getNom(),
                'description' => 'Réunion d\'équipe pour discuter du projet ' . $project->getNom(),
                'start' => $this->createEventDateTime($startTime->format('Y-m-d H:i:s')),
                'end' => $this->createEventDateTime($endTime->format('Y-m-d H:i:s')),
                'conferenceData' => [
                    'createRequest' => [
                        'requestId' => uniqid(),
                        'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                    ],
                ],
                'attendees' => array_map(fn($member) => ['email' => $member->getEmail()], $project->getMembers()->toArray()),
            ]);

            $event = $service->events->insert('primary', $event, ['conferenceDataVersion' => 1]);

            // Send email to members
            $meetLink = $event->getHangoutLink();
            foreach ($project->getMembers() as $member) {
                $email = (new Email())
                    ->from('no-reply@optirh.com')
                    ->to($member->getEmail())
                    ->subject('Invitation à la réunion Google Meet pour ' . $project->getNom())
                    ->html(
                        '<p>Bonjour ' . $member->getNom() . ',</p>' .
                        '<p>Une réunion a été planifiée pour le projet <strong>' . $project->getNom() . '</strong>.</p>' .
                        '<p><strong>Date et heure:</strong> ' . $startTime->format('d/m/Y H:i') . '</p>' .
                        '<p><strong>Lien Google Meet:</strong> <a href="' . $meetLink . '">' . $meetLink . '</a></p>' .
                        '<p>Cordialement,<br>L\'équipe Optirh</p>'
                    );

                $this->mailer->send($email);
            }

            $this->addFlash('success', 'Réunion Google Meet créée et invitations envoyées');
        } catch (\Exception $e) {
            $this->logger->error('Google Calendar create meet error: ' . $e->getMessage());
            $this->addFlash('error', 'Erreur lors de la création de la réunion: ' . $e->getMessage());
        }

        return $this->redirectToRoute('gs-projet_project_show', ['id' => $projectId]);
    }

    #[Route('/google-calendar/disconnect', name: 'google_calendar_disconnect')]
    public function disconnect(): RedirectResponse
    {
        $this->getSession()->remove('google_calendar_token');
        $this->addFlash('success', 'Déconnexion réussie');
        return $this->redirectToRoute('gs-projet_project_app_calendar');
    }

    private function initializeClient(): void
    {
        $token = $this->getSession()->get('google_calendar_token');
        if (!$token) {
            throw new \Exception('No Google Calendar token found in session');
        }

        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired()) {
            try {
                $refreshToken = $token['refresh_token'] ?? null;
                if (!$refreshToken) {
                    throw new \Exception('No refresh token available');
                }
                $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                if (isset($newToken['error'])) {
                    throw new \Exception('Token refresh error: ' . $newToken['error']);
                }
                $this->getSession()->set('google_calendar_token', $newToken);
            } catch (\Exception $e) {
                $this->logger->error('Token refresh error: ' . $e->getMessage());
                $this->getSession()->remove('google_calendar_token');
                throw new \Exception('Failed to refresh access token');
            }
        }
    }

    private function createEventDateTime(string $datetime): array
    {
        return [
            'dateTime' => (new DateTime($datetime))->format(DateTimeInterface::RFC3339),
            'timeZone' => 'Europe/Paris',
        ];
    }

    private function getSession()
    {
        return $this->requestStack->getSession();
    }
}