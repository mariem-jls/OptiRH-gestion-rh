<?php
namespace App\Service;

class MeetLinkGenerator
{
    private string $baseUrl = 'https://meet.jit.si/';

    public function createMeetLink(): string
    {
        // Génère un nom de salle unique (ex : project-4f7c9a12)
        $roomName = 'project-' . bin2hex(random_bytes(4)); // 8 caractères hex
        return $this->baseUrl . $roomName;
    }
}
