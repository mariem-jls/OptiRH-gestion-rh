<?php
namespace App\Controller\Admin; 

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotificationMissionController extends AbstractController
{
    private $entityManager;
    private $notificationRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        NotificationRepository $notificationRepository
    ) {
        $this->entityManager = $entityManager;
        $this->notificationRepository = $notificationRepository;
    }


    #[Route('/admin/notification/{id}/read', name: 'app_admin_notification_read')]
public function markAsRead(Notification $notification): Response
{
    $notification->markAsRead();
    $this->entityManager->flush();

    return $this->redirectToRoute(
        $notification->getRouteName(),
        $notification->getRouteParams() ?? []
    );
}
#[Route('/admin/notifications', name: 'app_admin_notifications')]
public function index(): Response
{
    $user = $this->getUser();
    $notifications = $this->notificationRepository->findBy(
        ['recipient' => $user],
        ['createdAt' => 'DESC']
    );

    return $this->render('notification/index.html.twig', [
        'notifications' => $notifications,
    ]);
}

#[Route('admin/notification/recent', name: 'app_notification_recent')]
public function recentNotifications(): Response
{
    $user = $this->getUser();
    $notifications = $this->notificationRepository->findBy(
        ['recipient' => $user],
        ['createdAt' => 'DESC'],
        5
    );

    return $this->render('notification/_recent.html.twig', [
        'notifications' => $notifications
    ]);
}

    #[Route('/admin/notification/count-unread', name: 'app_admin_notification_count_unread')]
    public function countUnread(): Response
    {
        $count = $this->notificationRepository->count([
            'recipient' => $this->getUser(),
            'isRead' => false
        ]);

        return new Response($count);
    }
}