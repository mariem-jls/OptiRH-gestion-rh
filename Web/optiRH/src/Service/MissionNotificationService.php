<?php

namespace App\Service;

use App\Entity\GsProjet\Mission;
use App\Entity\Notification;
use App\Service\NotificationManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use App\Entity\GsProjet\Project;
use App\Entity\User;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;



class MissionNotificationService
{
    private MailerInterface $mailer;
    private EntityManagerInterface $entityManager;
    private NotificationManager $notificationManager;
    private LoggerInterface $logger;
    private string $senderEmail;
    private string $senderName;

    public function __construct(
        MailerInterface $mailer,
        EntityManagerInterface $entityManager,
        NotificationManager $notificationManager,
        LoggerInterface $logger,
        string $senderEmail,
        string $senderName
    ) {
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
        $this->notificationManager = $notificationManager;
        $this->logger = $logger;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
    }

    public function sendNewMissionNotification(Mission $mission): void
    {
        $assignedTo = $mission->getAssignedTo();
        if (!$assignedTo) {
            $this->logger->info('Notification de nouvelle mission ignorée', [
                'mission_id' => $mission->getId(),
                'reason' => 'Aucun utilisateur assigné',
            ]);
            return;
        }

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
                'mission_id' => $mission->getId(),
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
        $notification->setRouteName('gs-projet_project_mission_show');
        $notification->setRouteParams(['id' => $mission->getId()]);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        $this->logger->info('Notification de nouvelle mission créée', [
            'notification_id' => $notification->getId(),
            'mission_id' => $mission->getId(),
        ]);
    }

    public function sendLateMissionEmail(Mission $mission): void
{
    $assignedTo = $mission->getAssignedTo();
    if (!$assignedTo || $mission->getStatus() === 'Done') {
        $this->logger->info('Email en retard ignoré', [
            'mission_id' => $mission->getId(),
            'reason' => !$assignedTo ? 'Aucun utilisateur assigné' : 'Statut Done',
        ]);
        return;
    }

    $daysLate = $mission->getDaysLate();
    if ($daysLate <= 0) {
        $this->logger->info('Email en retard ignoré', [
            'mission_id' => $mission->getId(),
            'reason' => 'Pas en retard',
            'days_late' => $daysLate,
        ]);
        return;
    }

    $this->logger->info('Envoi de l\'email en retard', [
        'mission_id' => $mission->getId(),
        'user_id' => $assignedTo->getId(),
        'days_late' => $daysLate,
    ]);

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
public function createLateMissionNotification(Mission $mission): void
{
    $assignedTo = $mission->getAssignedTo();
    if (!$assignedTo || $mission->getStatus() === 'Done') {
        return;
    }

    $daysLate = $mission->getDaysLate();
    if ($daysLate <= 0) {
        return;
    }

    $notification = new Notification();
    $notification->setRecipient($assignedTo);
    $notification->setMessage(sprintf(
        'Mission en retard: "%s" (en retard de %d jours)',
        $mission->getTitre(),
        $daysLate
    ));
    $notification->markAsUnread();
    $notification->setType(Notification::TYPE_LATE_MISSION);
    $notification->setRouteName('gs-projet_project_mission_show');
    $notification->setRouteParams(['id' => $mission->getId()]);
    $notification->setContext([
        'mission_id' => $mission->getId(),
        'days_late' => $daysLate,
    ]);

    $this->entityManager->persist($notification);

    $this->logger->info('Notification en retard créée', [
        'notification_id' => $notification->getId(),
        'mission_id' => $mission->getId(),
    ]);
}
public function sendMeetInvitation(Mission $mission): void
{
    $assignedTo = $mission->getAssignedTo();
    
    // Vérification si la mission a un utilisateur assigné
    if (!$assignedTo) {
        $this->logger->info('Invitation Meet ignorée - Aucun utilisateur assigné', [
            'mission_id' => $mission->getId()
        ]);
        return;
    }

    try {
        // Construction et envoi de l'email
        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($assignedTo->getEmail(), $assignedTo->getNom()))
            ->subject('Invitation réunion: ' . $mission->getTitre())
            ->htmlTemplate('gs-projet/project/Email/meet.html.twig')
            ->context([
                'mission' => $mission,
                'user' => $assignedTo,
                'meetLink' => $mission->getMeetLink(),
                'deadline' => $mission->getDateTerminer(),
                'project' => $mission->getProject(),
            ]);

        $this->mailer->send($email);

        $this->logger->info('Invitation Meet envoyée', [
            'mission_id' => $mission->getId(),
            'user_email' => $assignedTo->getEmail()
        ]);

    } catch (TransportExceptionInterface $e) {
        $this->logger->error('Erreur d\'envoi email', [
            'mission_id' => $mission->getId(),
            'error' => $e->getMessage()
        ]);
        throw new \RuntimeException('Erreur lors de l\'envoi de l\'email: '.$e->getMessage());
    }
}
}