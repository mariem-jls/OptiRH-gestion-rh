<?php
// src/Service/SlotSuggester.php
namespace App\Service;

use App\Entity\Demande;
use App\Repository\InterviewRepository;

class SlotSuggester
{
    private InterviewRepository $interviewRepository;

    public function __construct(InterviewRepository $interviewRepository)
    {
        $this->interviewRepository = $interviewRepository;
    }

    public function suggestSlots(Demande $demande): array
    {
        $slots = [];
        $today = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $endDate = (clone $today)->modify('+3 days');
        $availableHours = [
            9 => 'Matin',
            10 => 'Matin',
            11 => 'Matin',
            14 => 'Après-midi',
            15 => 'Après-midi',
            16 => 'Après-midi',
        ];

        while ($today <= $endDate) {
            foreach ($availableHours as $hour => $period) {
                $slotDateTime = (clone $today)->setTime($hour, 0);

                // Skip if slot is before Demande::$dateDebutDisponible
                if ($demande->getDateDebutDisponible() && $slotDateTime < $demande->getDateDebutDisponible()) {
                    continue;
                }

                // Check for conflicts
                if (!$this->interviewRepository->isSlotTaken($slotDateTime)) {
                    $slots[] = (object) [
                        'dateTime' => $slotDateTime, // Instance de DateTime
                        'period' => $period,
                        'priority' => $hour === 9 ? 'recommended' : 'available',
                    ];
                }
            }
            $today->modify('+1 day');
        }

        return $slots;
    }
}