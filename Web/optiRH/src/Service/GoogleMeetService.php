<?php
namespace App\Service;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\ConferenceSolutionKey;
use Google\Service\Calendar\CreateConferenceRequest;

class GoogleMeetService
{
    private $client;
    private $calendarId;

    public function __construct(string $clientSecretPath, string $calendarId = 'primary')
    {
        $this->client = new Client();
        $this->client->setAuthConfig($clientSecretPath);
        $this->client->addScope(Calendar::CALENDAR_EVENTS);
        $this->calendarId = $calendarId;
    }

    public function createMeeting(
        string $summary,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        string $organizerEmail
    ): array {
        $this->client->setSubject($organizerEmail);
        $calendarService = new Calendar($this->client);

        // Nouvelle méthode recommandée (2024)
        $event = new Event([
            'summary' => $summary,
            'start' => ['dateTime' => $startTime->format(\DateTimeInterface::RFC3339)],
            'end' => ['dateTime' => $endTime->format(\DateTimeInterface::RFC3339)],
        ]);

        // Configuration Meet
        $conferenceData = new ConferenceData([
            'createRequest' => [
                'conferenceSolutionKey' => [
                    'type' => 'hangoutsMeet'
                ],
                'requestId' => bin2hex(random_bytes(16))
            ]
        ]);

        $event->setConferenceData($conferenceData);

        try {
            $createdEvent = $calendarService->events->insert(
                $this->calendarId, 
                $event, 
                ['conferenceDataVersion' => 1]
            );

            return [
                'meetLink' => $createdEvent->getHangoutLink(),
                'eventId' => $createdEvent->getId(),
                'joinUrl' => $createdEvent->conferenceData->entryPoints[0]->uri ?? null
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException('Google Meet creation failed: '.$e->getMessage());
        }
    }
}