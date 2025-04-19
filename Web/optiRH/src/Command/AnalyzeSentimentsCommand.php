<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use App\Service\SentimentAnalysisService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnalyzeSentimentsCommand extends Command
{
    protected static $defaultName = 'app:analyze-sentiments';

    private EntityManagerInterface $em;
    private SentimentAnalysisService $sentimentAnalyzer;

    public function __construct(EntityManagerInterface $em, SentimentAnalysisService $sentimentAnalyzer)
    {
        $this->em = $em;
        $this->sentimentAnalyzer = $sentimentAnalyzer;
        
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $reclamations = $this->em->getRepository(Reclamation::class)
            ->findBy(['sentimentScore' => null]);

        foreach ($reclamations as $reclamation) {
            $output->writeln(sprintf(
                'Analyzing reclamation #%d: %s...',
                $reclamation->getId(),
                substr($reclamation->getDescription(), 0, 30)
            ));

            $sentiment = $this->sentimentAnalyzer->analyze($reclamation->getDescription());
            $reclamation->setSentimentScore($sentiment['score']);
            $reclamation->setSentimentLabel($sentiment['label']);

            $this->em->flush();
        }

        $output->writeln(sprintf('Analyzed %d reclamations.', count($reclamations)));

        return Command::SUCCESS;
    }
}