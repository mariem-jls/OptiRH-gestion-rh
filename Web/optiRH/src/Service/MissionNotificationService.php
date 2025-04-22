<?php
namespace App\Service;

use App\Entity\GsProjet\Mission;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class MissionNotificationService
{
    private MailerInterface $mailer;
    private string $senderEmail;
    private string $senderName;

    public function __construct(
        MailerInterface $mailer,
        string $senderEmail,
        string $senderName
    ) {
        $this->mailer = $mailer;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
    }

    public function sendNewMissionNotification(Mission $mission): void
    {
        $assignedTo = $mission->getAssignedTo();
        if (!$assignedTo) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($assignedTo->getEmail(), $assignedTo->getNom()))
            ->subject('Nouvelle mission assignée: ' . $mission->getTitre())
            ->htmlTemplate('gs-projet/project/Email/new_mission.html.twig')
            ->context([
                'mission' => $mission,
                'user' => $assignedTo,
                'deadline' => $mission->getDateTerminer(),
            ]);

        $this->mailer->send($email);
    }

    public function sendLateMissionNotification(Mission $mission): void
    {
        $assignedTo = $mission->getAssignedTo();
        if (!$assignedTo || $mission->getStatus() === 'Done') {
            return;
        }

        $daysLate = $mission->getDaysLate();
        if ($daysLate <= 0) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($assignedTo->getEmail(), $assignedTo->getNom()))
            ->subject('MISSION EN RETARD: ' . $mission->getTitre())
            ->htmlTemplate('gs-projet/project/Email/mission_enRetard.html.twig')
            ->context([
                'mission' => $mission,
                'user' => $assignedTo,
                'daysLate' => $daysLate,
            ]);

        $this->mailer->send($email);
    }
}