<?php
namespace App\Service;

use App\Repository\GsProjet\MissionRepository;

class CalendarManager
{
    private $missionRepository;

    public function __construct(MissionRepository $missionRepository)
    {
        $this->missionRepository = $missionRepository;
    }

    public function getCalendarEvents(array $filters = []): array
    {
        $missions = $this->missionRepository->findWithFilters($filters);
        
        return array_map(function($mission) {
            return [
                'id' => $mission->getId(),
                'title' => $mission->getTitle(),
                'start' => $mission->getDeadline()->format('Y-m-d'),
                'color' => $this->getStatusColor($mission->getStatut()),
                'extendedProps' => [
                    'statut' => $mission->getStatut(),
                    'description' => $mission->getDescription(),
                    'isLate' => $mission->isLate(),
                    'projectTitle' => $mission->getProject()?->getName()
                ]
            ];
        }, $missions);
    }

    private function getStatusColor(string $status): string
    {
        return match($status) {
            'Done' => '#06d6a0',
            'In Progress' => '#ffd166',
            'To Do' => '#ff6b6b',
            default => '#6c757d'
        };
    }
}