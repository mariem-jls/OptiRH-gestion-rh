<?php
namespace App\EventSubscriber;

use App\Entity\Notification;
use App\Repository\GsProjet\MissionRepository;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LoginSubscriber implements EventSubscriberInterface
{
    private $missionRepository;
    private $entityManager;
    private $notificationRepository;

    public function __construct(
        MissionRepository $missionRepository,
        EntityManagerInterface $entityManager,
        NotificationRepository $notificationRepository
    ) {
        $this->missionRepository = $missionRepository;
        $this->entityManager = $entityManager;
        $this->notificationRepository = $notificationRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        
        $lateMissions = $this->missionRepository->findOverdueMissionsForUser($user);

        foreach ($lateMissions as $mission) {
            // Vérifie si une notification existe déjà via le contexte
            $existingNotification = $this->notificationRepository->findOneBy([
                'recipient' => $user,
                'type' => Notification::TYPE_LATE_MISSION,
                'context' => ['mission_id' => $mission->getId()]
            ]);

            if (!$existingNotification && !$mission->isNotifiedLate()) {
                $notification = new Notification();
                $notification->setRecipient($user);
                $notification->setMessage(sprintf(
                    'Mission en retard: "%s" devait être terminée le %s (%d jours de retard)',
                    $mission->getTitre(),  // Utilisation de getTitre() au lieu de getTitle()
                    $mission->getDateTerminer()->format('d/m/Y'),
                    $mission->getDaysLate()
                ));
                $notification->markAsUnread(); // Utilisation de markAsUnread() au lieu de IsRead(false)
                $notification->setType(Notification::TYPE_LATE_MISSION);
                $notification->setContext([
                    'mission_id' => $mission->getId(),
                    'project_id' => $mission->getProject()?->getId(),
                    'days_late' => $mission->getDaysLate()
                ]);
               
                $notification->setRouteName('gs-projet_project_mission_show');
                $notification->setRouteParams(['id' => $mission->getId()]);

                $this->entityManager->persist($notification);
                $mission->setNotifiedLate(true);
                $this->entityManager->persist($mission);
            }
        }

        $this->entityManager->flush();
    }
}