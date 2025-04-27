<?php

namespace App\Controller;

use Google\Client as Google_Client;
use Google\Service\Calendar as Google_Service_Calendar;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * Controller for Google Calendar integration, handling OAuth authentication and event retrieval.
 */
class GoogleCalendarController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Initializes the Google Client with OAuth settings.
     */
    private function initializeGoogleClient(): Google_Client
    {
        $client = new Google_Client();
        $client->setApplicationName('MissionProjet');
        $client->setScopes([Google_Service_Calendar::CALENDAR_READONLY]);
        $client->setClientId($this->getParameter('GOOGLE_CLIENT_ID'));
        $client->setClientSecret($this->getParameter('GOOGLE_CLIENT_SECRET'));
        $redirectUri = $this->getParameter('GOOGLE_REDIRECT_URI');
        $client->setRedirectUri($redirectUri);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $client->setIncludeGrantedScopes(true);

        $this->logger->debug('Initialized Google Client', [
            'redirect_uri' => $redirectUri,
            'scopes' => $client->getScopes(),
            'client_id' => substr($this->getParameter('GOOGLE_CLIENT_ID'), 0, 10) . '...'
        ]);

        return $client;
    }

    /**
     * Starts the OAuth authentication flow by generating an authorization URL.
     */
    #[Route('/google-calendar/auth', name: 'google_calendar_auth', methods: ['GET'])]
    public function auth(Request $request): JsonResponse
    {
        $session = $request->getSession();
        
        if (!$session->isStarted()) {
            $session->start();
            $this->logger->debug('Started new session', ['session_id' => $session->getId()]);
        }

        $session->remove('oauth_state');
        $session->remove('google_access_token');
        $session->remove('google_refresh_token');

        $client = $this->initializeGoogleClient();

        $state = bin2hex(random_bytes(32));
        $session->set('oauth_state', $state);
        $session->save();
        $client->setState($state);

        $authUrl = $client->createAuthUrl();

        $this->logger->info('Auth: Initiated OAuth flow', [
            'state' => $state,
            'session_id' => $session->getId(),
            'auth_url' => $authUrl
        ]);

        return new JsonResponse([
            'authUrl' => $authUrl,
            'session_id' => $session->getId()
        ]);
    }

    /**
     * Handles the OAuth callback, validates the state, and stores the access token.
     */
    #[Route('/google-calendar/callback', name: 'google_calendar_callback', methods: ['GET'])]
    public function callback(Request $request, SessionInterface $session): JsonResponse
    {
        if (!$session->isStarted()) {
            $session->start();
            $this->logger->warning('Session was not started in callback', ['session_id' => $session->getId()]);
        }

        $sessionState = $session->get('oauth_state');
        $requestState = $request->query->get('state');
        $queryParams = $request->query->all();
        $callbackUrl = $request->getUri();

        $this->logger->debug('Callback: State validation', [
            'session_state' => $sessionState ?? 'null',
            'request_state' => $requestState ?? 'null',
            'session_id' => $session->getId(),
            'callback_url' => $callbackUrl,
            'query_params' => $queryParams
        ]);

        if (!$requestState || !$sessionState || !hash_equals($sessionState, $requestState)) {
            $session->remove('oauth_state');
            $this->logger->warning('Invalid OAuth state parameter', [
                'session_state' => $sessionState,
                'request_state' => $requestState
            ]);
            return new JsonResponse([
                'error' => 'Invalid state parameter',
                'solution' => 'Restart the OAuth flow by visiting /google-calendar/auth'
            ], 400);
        }

        $session->remove('oauth_state');

        $client = $this->initializeGoogleClient();
        $code = $request->query->get('code');

        if (!$code) {
            $this->logger->error('No OAuth code provided in callback', ['query' => $queryParams]);
            return new JsonResponse(['error' => 'No code provided'], 400);
        }

        try {
            $accessToken = $client->fetchAccessTokenWithAuthCode($code);
            $this->logger->debug('OAuth callback response', [
                'access_token' => $accessToken,
                'session_id' => $session->getId()
            ]);

            if (isset($accessToken['error'])) {
                $this->logger->error('OAuth token error', [
                    'error' => $accessToken['error'],
                    'description' => $accessToken['error_description'] ?? null,
                    'callback_url' => $callbackUrl
                ]);
                return new JsonResponse([
                    'error' => $accessToken['error'],
                    'description' => $accessToken['error_description'] ?? null
                ], 400);
            }

            if (empty($accessToken['access_token'])) {
                $this->logger->error('Invalid token response', ['response' => $accessToken]);
                return new JsonResponse(['error' => 'Invalid token response'], 400);
            }

            $session->set('google_access_token', $accessToken);
            $session->save();

            if (!empty($accessToken['refresh_token'])) {
                $session->set('google_refresh_token', $accessToken['refresh_token']);
                $cache = new FilesystemAdapter();
                $cacheItem = $cache->getItem('google_refresh_token_' . $session->getId());
                $cacheItem->set($accessToken['refresh_token']);
                $cacheItem->expiresAfter(86400 * 30);
                $cache->save($cacheItem);
                $this->logger->info('Stored refresh token', [
                    'session_id' => $session->getId(),
                    'cache_key' => 'google_refresh_token_' . $session->getId()
                ]);
            }

            return new JsonResponse([
                'status' => 'success',
                'expires_in' => $accessToken['expires_in'] ?? null
            ]);

        } catch (\Google\Service\Exception $e) {
            $this->logger->error('Google API error during OAuth callback', [
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
                'code' => $e->getCode(),
                'callback_url' => $callbackUrl
            ]);
            return new JsonResponse([
                'error' => 'Google API error',
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ], 500);
        } catch (\Exception $e) {
            $this->logger->error('OAuth callback failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'callback_url' => $callbackUrl
            ]);
            return new JsonResponse([
                'error' => 'Authentication failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retrieves Google Calendar events for the authenticated user.
     */
    #[Route('/google-calendar/events', name: 'google_calendar_events', methods: ['GET'])]
    public function getEvents(Request $request, SessionInterface $session): JsonResponse
    {
        if (!$session->isStarted()) {
            $session->start();
            $this->logger->warning('Session was not started in getEvents', ['session_id' => $session->getId()]);
        }

        if (!$session->has('google_access_token')) {
            $this->logger->warning('No access token in session', ['session_id' => $session->getId()]);
            return new JsonResponse([
                'error' => 'Non authentifié',
                'solution' => $this->generateUrl('google_calendar_auth')
            ], 401);
        }

        $client = $this->initializeGoogleClient();
        $accessToken = $session->get('google_access_token');
        $client->setAccessToken($accessToken);

        $this->logger->debug('GetEvents: Access token', [
            'access_token' => $accessToken,
            'session_id' => $session->getId()
        ]);

        if ($client->isAccessTokenExpired()) {
            try {
                $refreshToken = $session->get('google_refresh_token') ??
                    (new FilesystemAdapter())->getItem('google_refresh_token_' . $session->getId())->get();

                if (!$refreshToken) {
                    $this->logger->error('No refresh token available', ['session_id' => $session->getId()]);
                    throw new \RuntimeException('No refresh token available');
                }

                $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
                $this->logger->debug('Token refresh response', [
                    'new_token' => $newToken,
                    'session_id' => $session->getId()
                ]);

                if (isset($newToken['error'])) {
                    $this->logger->error('Token refresh failed', [
                        'error' => $newToken['error'],
                        'description' => $newToken['error_description'] ?? null
                    ]);
                    throw new \RuntimeException($newToken['error_description'] ?? $newToken['error']);
                }

                $session->set('google_access_token', $newToken);
                $session->save();
                if (!empty($newToken['refresh_token'])) {
                    $session->set('google_refresh_token', $newToken['refresh_token']);
                    $cache = new FilesystemAdapter();
                    $cacheItem = $cache->getItem('google_refresh_token_' . $session->getId());
                    $cacheItem->set($newToken['refresh_token']);
                    $cacheItem->expiresAfter(86400 * 30);
                    $cache->save($cacheItem);
                }
            } catch (\Exception $e) {
                $this->logger->error('Token refresh failed', [
                    'message' => $e->getMessage(),
                    'session_id' => $session->getId()
                ]);
                $session->remove('google_access_token');
                $session->remove('google_refresh_token');
                return new JsonResponse([
                    'error' => 'Non authentifié',
                    'message' => $e->getMessage(),
                    'solution' => $this->generateUrl('google_calendar_auth')
                ], 401);
            }
        }

        try {
            $service = new Google_Service_Calendar($client);
            $events = $service->events->listEvents('primary', [
                'timeMin' => (new \DateTime())->format(\DateTime::RFC3339),
                'timeMax' => (new \DateTime('+1 month'))->format(\DateTime::RFC3339),
                'singleEvents' => true,
                'orderBy' => 'startTime',
                'maxResults' => 100
            ]);

            $formattedEvents = [];
            foreach ($events->getItems() as $event) {
                $formattedEvents[] = [
                    'id' => $event->getId(),
                    'title' => $event->getSummary() ?? 'Sans titre',
                    'start' => $event->getStart()->getDateTime() ?: $event->getStart()->getDate(),
                    'end' => $event->getEnd()->getDateTime() ?: $event->getEnd()->getDate(),
                    'description' => $event->getDescription() ?? '',
                    'location' => $event->getLocation() ?? '',
                    'creator' => $event->getCreator() ? $event->getCreator()->getEmail() : null,
                    'source' => 'google',
                    'backgroundColor' => '#4285F4',
                    'borderColor' => '#4285F4'
                ];
            }

            $this->logger->info('Retrieved Google Calendar events', [
                'count' => count($formattedEvents),
                'session_id' => $session->getId()
            ]);

            return new JsonResponse([
                'events' => $formattedEvents,
                'timeZone' => $events->getTimeZone()
            ]);

        } catch (\Google\Service\Exception $e) {
            $this->logger->error('Google API error fetching events', [
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
                'code' => $e->getCode(),
                'session_id' => $session->getId()
            ]);
            return new JsonResponse([
                'error' => 'Échec de la récupération des événements',
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ], 500);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch events', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'session_id' => $session->getId()
            ]);
            return new JsonResponse([
                'error' => 'Échec de la récupération des événements',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Provides debug information about the session and cache.
     */
    #[Route('/google-calendar/debug', name: 'google_calendar_debug', methods: ['GET'])]
    public function debug(SessionInterface $session): JsonResponse
    {
        if (!$session->isStarted()) {
            $session->start();
            $this->logger->warning('Session was not started in debug', ['session_id' => $session->getId()]);
        }

        $cache = new FilesystemAdapter();
        $refreshTokenCache = $cache->getItem('google_refresh_token_' . $session->getId());

        return new JsonResponse([
            'session' => [
                'id' => $session->getId(),
                'access_token' => $session->has('google_access_token'),
                'refresh_token' => $session->has('google_refresh_token'),
                'oauth_state' => $session->has('oauth_state')
            ],
            'cache' => [
                'refresh_token' => $refreshTokenCache->isHit(),
                'refresh_token_value' => $refreshTokenCache->isHit() ? 'present' : 'not present'
            ],
            'environment' => [
                'redirect_uri' => $this->getParameter('GOOGLE_REDIRECT_URI'),
                'client_id_set' => !empty($this->getParameter('GOOGLE_CLIENT_ID')),
                'scopes' => [Google_Service_Calendar::CALENDAR_READONLY]
            ]
        ]);
    }

    /**
     * Disconnects the Google Calendar integration by clearing tokens.
     */
    #[Route('/google-calendar/disconnect', name: 'google_calendar_disconnect', methods: ['GET'])]
    public function disconnect(SessionInterface $session): JsonResponse
    {
        if (!$session->isStarted()) {
            $session->start();
            $this->logger->warning('Session was not started in disconnect', ['session_id' => $session->getId()]);
        }

        $cache = new FilesystemAdapter();
        $cache->delete('google_refresh_token_' . $session->getId());

        $session->remove('google_access_token');
        $session->remove('google_refresh_token');
        $session->remove('oauth_state');

        $this->logger->info('Google Calendar disconnected', ['session_id' => $session->getId()]);

        return new JsonResponse(['status' => 'success']);
    }
}