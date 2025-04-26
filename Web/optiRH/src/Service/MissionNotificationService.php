<?php
namespace App\Service;

use App\Entity\GsProjet\Mission;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
class MissionNotificationService
{
    private MailerInterface $mailer;
    private EntityManagerInterface $entityManager;
    private string $senderEmail;
    private string $senderName;

    public function __construct(
        MailerInterface $mailer,
        EntityManagerInterface $entityManager,
        string $senderEmail,
        string $senderName
    ) {
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
    }

    public function sendNewMissionNotification(Mission $mission): void
    {
        $assignedTo = $mission->getAssignedTo();
    if (!$assignedTo) {
        return;
    }

    // Vérification que la mission a un ID
    if (!$mission->getId()) {
        throw new \RuntimeException('La mission doit être persistée avant d\'envoyer des notifications');
    }

    $email = (new TemplatedEmail())
        ->from(new Address($this->senderEmail, $this->senderName))
        ->to(new Address($assignedTo->getEmail(), $assignedTo->getNom()))
        ->subject('Nouvelle mission assignée: ' . $mission->getTitre())
        ->htmlTemplate('notification/new_mission.html.twig')
        ->context([
            'mission' => $mission,
            'user' => $assignedTo,
            'deadline' => $mission->getDateTerminer(),
            'mission_id' => $mission->getId() // Ajout explicite de l'ID
        ]);

    $this->mailer->send($email);
    $notification = new Notification();
    $notification->setRecipient($assignedTo);
    $notification->setMessage(sprintf(
        'Nouvelle mission: "%s" (à terminer avant %s)',
        $mission->getTitre(),
        $mission->getDateTerminer()->format('d/m/Y')
    ));
    $notification->markAsUnread();
    $notification->setType(Notification::TYPE_NEW_MISSION);
    $notification->setRouteName('gs-projet_project_mission_show'); // Route garantie
    $notification->setRouteParams(['id' => $mission->getId()]); // Paramètres garantis

    $this->entityManager->persist($notification);
    $this->entityManager->flush();

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