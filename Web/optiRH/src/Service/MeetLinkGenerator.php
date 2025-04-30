<?php
namespace App\Service;
use App\Entity\GsProjet\Project; 

class MeetLinkGenerator
{
    private string $baseUrl = 'https://meet.jit.si/';

    public function createMeetLink(Project $project): string
    {
        // Normalise le nom du projet pour l'URL (enlève les espaces et caractères spéciaux)
        $projectName = preg_replace('/[^a-zA-Z0-9]/', '-', $project->getNom());
        $projectName = strtolower($projectName);
        
        // Ajoute un identifiant unique court pour éviter les conflits
        $uniqueId = bin2hex(random_bytes(2)); // 4 caractères hex
        
        // Crée le nom de salle : "nom-projet-1234"
        $roomName = 'optirh-' . $projectName . '-' . $uniqueId;
        
        return $this->baseUrl . $roomName;
    }
}