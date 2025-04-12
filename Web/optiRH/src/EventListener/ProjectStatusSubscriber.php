<?php

namespace App\EventListener;

use App\Entity\GsProjet\Mission;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

class ProjectStatusSubscriber implements EventSubscriberInterface
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
        ];
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->handleEvent($args);
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->handleEvent($args);
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->handleEvent($args);
    }

    private function handleEvent($args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Mission) {
            $project = $entity->getProject();
            $project->updateStatus();
            
            $entityManager = $args->getObjectManager();
            $entityManager->persist($project);
            $entityManager->flush();
        }
    }
}