<?php
// src/Controller/InterviewController.php
namespace App\Controller;

use App\Entity\Demande;
use App\Entity\Interview;
use App\Form\InterviewType;
use App\Repository\DemandeRepository;
use App\Repository\OffreRepository;
use App\Repository\InterviewRepository;
use App\Service\SlotSuggester;
use App\Service\EmailService;
use App\Service\GoogleMeetService;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


class InterviewController extends AbstractController
{
    #[Route('/plan-interview/{demandeId}', name: 'plan_interview', methods: ['GET', 'POST'])]
    public function plan(
        int $demandeId,
        Request $request,
        DemandeRepository $demandeRepository,
        InterviewRepository $interviewRepository,
        SlotSuggester $slotSuggester
    ): Response {
        $demande = $demandeRepository->find($demandeId);
        if (!$demande) {
            throw $this->createNotFoundException('Demande non trouvée');
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Seuls les recruteurs peuvent planifier des entretiens.');

        $interview = new Interview();
        $interview->setDemande($demande);
        $form = $this->createForm(InterviewType::class, $interview);

        $suggestedSlots = $slotSuggester->suggestSlots($demande);

        return $this->render('interview/plan.html.twig', [
            'form' => $form->createView(),
            'demande' => $demande,
            'suggested_slots' => $suggestedSlots,
        ]);
    }

    #[Route('/api/interview/suggest-slots/{demandeId}', name: 'api_suggest_slots', methods: ['GET'])]
    public function suggestSlots(
        int $demandeId,
        DemandeRepository $demandeRepository,
        SlotSuggester $slotSuggester
    ): JsonResponse {
        $demande = $demandeRepository->find($demandeId);
        if (!$demande) {
            return new JsonResponse(['error' => 'Demande non trouvée'], 404);
        }

        $slots = $slotSuggester->suggestSlots($demande);
        $formattedSlots = array_map(function ($slot) {
            return [
                'dateTime' => $slot->dateTime->format('Y-m-d H:i'),
                'period' => $slot->period,
                'priority' => $slot->priority,
            ];
        }, $slots);

        return new JsonResponse(['slots' => $formattedSlots]);
    }

    private function getGoogleClient(Request $request): Google_Client
    {
        $session = $request->getSession();

        $client = new Google_Client();
        $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
        $client->setScopes(['https://www.googleapis.com/auth/calendar']);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        $accessToken = $session->get('google_access_token');
        if ($accessToken) {
            $client->setAccessToken($accessToken);
            if ($client->isAccessTokenExpired()) {
                $newAccessToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                $session->set('google_access_token', $newAccessToken);
            }
        }

        return $client;
    }

    #[Route('/auth/google', name: 'auth_google')]
    public function auth(Request $request): JsonResponse
    {
        $client = $this->getGoogleClient($request);
        $authUrl = $client->createAuthUrl();
        return new JsonResponse(['authUrl' => $authUrl]);
    }

    #[Route('/auth/google/callback', name: 'auth_google_callback')]
    public function callback(Request $request, DemandeRepository $demandeRepository, LoggerInterface $logger): RedirectResponse
    {
        $session = $request->getSession();
        $client = $this->getGoogleClient($request);
        $code = $request->query->get('code');
        if (!$code) {
            throw new \Exception('Code d’autorisation manquant');
        }

        $accessToken = $client->fetchAccessTokenWithAuthCode($code);
        $session->set('google_access_token', $accessToken);

        // Récupérer current_demande_id sans valeur par défaut
        $demandeId = $session->get('current_demande_id');
        if ($demandeId === null) {
            $logger->error('current_demande_id non défini dans la session lors de la redirection après authentification Google');
            // Rediriger vers une page par défaut ou afficher une erreur
            return $this->redirectToRoute('admin_home');
        }

        $demande = $demandeRepository->find($demandeId);
        if (!$demande) {
            $logger->error('Demande non trouvée pour ID: ' . $demandeId . ' après authentification Google');
            throw new \Exception('Demande non trouvée après authentification');
        }

        $logger->info('Redirection vers plan_interview avec demandeId: ' . $demandeId);
        return $this->redirectToRoute('plan_interview', ['demandeId' => $demande->getId()]);
    }

    #[Route('/api/check-auth', name: 'api_check_auth', methods: ['GET'])]
    public function checkAuth(Request $request): JsonResponse
    {
        $client = $this->getGoogleClient($request);
        $authenticated = !empty($client->getAccessToken());
        return new JsonResponse(['authenticated' => $authenticated]);
    }
    private function fetchGoogleMeetLink(): string
    {
        $meetLinks = [
            'https://meet.google.com/bwh-asmq-agw',
        ];

        return $meetLinks[array_rand($meetLinks)];
    }
    #[Route('/api/interview/select-slot/{demandeId}', name: 'api_select_slot', methods: ['POST'])]
    public function selectSlot(
        int $demandeId,
        Request $request,
        DemandeRepository $demandeRepository,
        InterviewRepository $interviewRepository,
        EmailService $emailService,
        LoggerInterface $logger
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Seuls les recruteurs peuvent planifier des entretiens.');

        $session = $request->getSession();
        $session->set('current_demande_id', $demandeId);

        $demande = $demandeRepository->find($demandeId);
        if (!$demande) {
            $logger->error("Demande ID $demandeId non trouvée.");
            return new JsonResponse(['error' => 'Demande non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['dateTime'])) {
            $logger->error('Données incomplètes dans la requête : ' . json_encode($data));
            return new JsonResponse(['error' => 'Données incomplètes'], 400);
        }

        try {
            $logger->info('DateTime reçu - DateTime reçu : ' . $data['dateTime']);
            $dateTime = \DateTime::createFromFormat('Y-m-d H:i', $data['dateTime'], new \DateTimeZone('Europe/Paris'));
            if ($dateTime === false) {
                throw new \Exception('Format de dateTime invalide : ' . $data['dateTime']);
            }

            if ($interviewRepository->isSlotTaken($dateTime)) {
                $logger->warning('Conflit d\'horaire détecté pour l\'entretien à ' . $dateTime->format('Y-m-d H:i'));
                return new JsonResponse(['error' => 'Ce créneau horaire est déjà pris. Veuillez choisir un autre horaire.'], 409);
            }

            // Simuler la génération d'un lien Google Meet
            sleep(1); // Simule un délai réseau de 1 seconde
            $meetLink = $this->fetchGoogleMeetLink();
            $logger->info('Lien Google Meet généré : ' . $meetLink);

            $interview = new Interview();
            $interview->setDemande($demande)
                ->setDateTime($dateTime)
                ->setGoogleMeetLink($meetLink);

            $interviewRepository->save($interview, true);
            $logger->info('Entretien sauvegardé pour la demande ID: ' . $demandeId . ' à ' . $dateTime->format('Y-m-d H:i'));

            $email = $demande->getEmail();
            if (!$email || !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
                $logger->warning("Email invalide pour la demande ID $demandeId : " . $email);
                throw new \Exception('Adresse email du candidat invalide.');
            }

            try {
                // On passe directement $dateTime, mais on s'assure que le template gère correctement la chaîne
                $emailService->sendInterviewConfirmationEmail(
                    $email,
                    $demande->getNomComplet(),
                    $demande->getOffre() ? $demande->getOffre()->getPoste() : 'Poste non spécifié',
                    $interview->getDateTime(),
                    $interview->getGoogleMeetLink()
                );
                $logger->info("Email de confirmation envoyé à $email pour un entretien le " . $dateTime->format('Y-m-d H:i'));
            } catch (\Exception $e) {
                $logger->warning('Erreur lors de l\'envoi de l\'email de confirmation à ' . $email . ': ' . $e->getMessage());
            }

            $logger->info('Entretien planifié via API pour la demande ID: ' . $demandeId);
            return new JsonResponse([
                'status' => 'success',
                'interviewId' => $interview->getId(),
                'dateTime' => $interview->getDateTime()->format('Y-m-d H:i'),
                'googleMeetLink' => $meetLink
            ]);
        } catch (\Exception $e) {
            $logger->error('Erreur lors de la planification via API pour demande ID ' . $demandeId . ': ' . $e->getMessage(), [
                'exception' => $e,
                'data' => $data,
            ]);
            return new JsonResponse(['error' => 'Erreur lors de la planification : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/interview/cancel/{interviewId}', name: 'api_cancel_interview', methods: ['DELETE'])]
    public function cancelInterview(
        int $interviewId,
        InterviewRepository $interviewRepository,
        EmailService $emailService,
        LoggerInterface $logger
    ): JsonResponse {
        // Vérifier les autorisations
        try {
            $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Seuls les recruteurs peuvent annuler des entretiens.');
        } catch (\Exception $e) {
            $logger->error('Accès refusé pour l\'annulation de l\'entretien ID: ' . $interviewId . '. Erreur: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Accès non autorisé'], 403);
        }

        // Trouver l'entretien
        $interview = $interviewRepository->find($interviewId);
        if (!$interview) {
            $logger->error('Entretien non trouvé pour ID: ' . $interviewId);
            return new JsonResponse(['error' => 'Entretien non trouvé'], 404);
        }

        $demande = $interview->getDemande();
        if (!$demande) {
            $logger->error('Demande non trouvée pour l\'entretien ID: ' . $interviewId);
            return new JsonResponse(['error' => 'Demande associée non trouvée'], 500);
        }

        $logger->info('Annulation de l\'entretien ID: ' . $interviewId . ' pour la demande ID: ' . $demande->getId());

        // Supprimer l'entretien
        try {
            $interviewRepository->remove($interview, true);
            $logger->info('Entretien ID: ' . $interviewId . ' supprimé avec succès');
        } catch (\Exception $e) {
            $logger->error('Erreur lors de la suppression de l\'entretien ID: ' . $interviewId . '. Erreur: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Erreur lors de la suppression de l\'entretien'], 500);
        }

        // Envoyer l'email de notification
        try {
            $offre = $demande->getOffre();
            $poste = $offre ? $offre->getPoste() : 'Poste non spécifié';
            $emailService->sendInterviewCancellationEmail(
                $demande->getEmail(),
                $demande->getNomComplet(),
                $poste,
                $interview->getDateTime()
            );
            $logger->info('Email d\'annulation envoyé à: ' . $demande->getEmail());
        } catch (\Exception $e) {
            $logger->warning('Erreur lors de l\'envoi de l\'email d\'annulation à ' . $demande->getEmail() . ': ' . $e->getMessage());
            // Continuer même si l'email échoue
        }

        return new JsonResponse(['status' => 'success']);
    }
    #[Route('/admin/interviews', name: 'admin_interviews', methods: ['GET'])]
    public function index(
        InterviewRepository $interviewRepository,
        DemandeRepository $demandeRepository,
        OffreRepository $offreRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Seuls les admins peuvent voir la liste des entretiens.');

        // Récupérer tous les interviews triés par date décroissante
        $interviews = $interviewRepository->findBy([], ['dateTime' => 'DESC']);

        // Calcul des statistiques
        $totalInterviews = count($interviews);
        $totalDemands = count($demandeRepository->findAll());
        $totalOffers = count($offreRepository->findAll());

        // Statistiques des demandes avec entretien
        $demandsWithInterview = count($demandeRepository->createQueryBuilder('d')
            ->innerJoin('d.interviews', 'i')
            ->getQuery()
            ->getResult());

        return $this->render('interview/index.html.twig', [
            'interviews' => $interviews,
            'totalInterviews' => $totalInterviews,
            'totalDemands' => $totalDemands,
            'totalOffers' => $totalOffers,
            'demandsWithInterview' => $demandsWithInterview,
        ]);
    }
}