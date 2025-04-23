<?php
namespace App\Command;

use App\Entity\GsProjet\Mission;
use App\Repository\GsProjet\MissionRepository;
use App\Service\MissionNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckLateMissionsCommand extends Command
{
    protected static $defaultName = 'app:check-late-missions';

    private MissionRepository $missionRepository;
    private MissionNotificationService $notificationService;
    private EntityManagerInterface $em;

    public function __construct(
        MissionRepository $missionRepository,
        MissionNotificationService $notificationService,
        EntityManagerInterface $em
    ) {
        parent::__construct();
        $this->missionRepository = $missionRepository;
        $this->notificationService = $notificationService;
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setDescription('Vérifie les missions en retard et envoie des notifications');
    }
// src/Command/CheckLateMissionsCommand.php

protected function execute(InputInterface $input, OutputInterface $output): int
{
    $today = new \DateTime();
    $output->writeln(sprintf('Vérification des missions en retard au %s', $today->format('d/m/Y')));
    
    $missions = $this->missionRepository->findLateMissions();
    $count = 0;

    if (empty($missions)) {
        $output->writeln('Aucune mission en retard trouvée');
        return Command::SUCCESS;
    }

    foreach ($missions as $mission) {
        try {
            $daysLate = $today->diff($mission->getDateTerminer())->days;
            $output->writeln(sprintf(
                'Mission #%d "%s" en retard de %d jours', 
                $mission->getId(),
                $mission->getTitre(),
                $daysLate
            ));

            $this->notificationService->sendLateMissionNotification($mission);
            $count++;

            // Marquer comme notifié pour éviter les répétitions
            $mission->setNotifiedLate(true);
            $this->em->persist($mission);
            
        } catch (\Exception $e) {
            $output->writeln(sprintf(
                'ERREUR Mission #%d: %s',
                $mission->getId(),
                $e->getMessage()
            ));
        }
    }

    $this->em->flush();
    $output->writeln(sprintf('%d notifications envoyées avec succès', $count));
    
    return Command::SUCCESS;
}
}