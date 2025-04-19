<?php
// src/Service/EmailNotificationService.php
namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Entity\Reclamation;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class EmailNotificationService
{
    private $mailer;
    private $adminEmail;
    private $urlGenerator;
    private $logger;

    public function __construct(
        MailerInterface $mailer, 
        string $adminEmail,
        UrlGeneratorInterface $urlGenerator,
        LoggerInterface $logger
    ) {
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
    }

    public function sendNegativeReclamationNotification(Reclamation $reclamation): bool
    {
        try {
            $email = (new Email())
                ->from('maram.rachdi11@gmail.com')
                ->to($this->adminEmail)
                ->subject('Réclamation négative #'.$reclamation->getId())
                ->html($this->createEmailContent($reclamation));

            $this->mailer->send($email);
            $this->logger->info('Email envoyé pour réclamation #'.$reclamation->getId());
            return true;
            
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Échec envoi email: '.$e->getMessage());
            return false;
        }
    }

    private function createEmailContent(Reclamation $reclamation): string
    {
        return sprintf(
            '<h2>Nouvelle réclamation négative</h2>
            <p><strong>Type:</strong> %s</p>
            <p><strong>Score:</strong> %s/1</p>
            <p><strong>Description:</strong><br>%s</p>
            <p><a href="%s">Gérer cette réclamation</a></p>',
            $reclamation->getType(),
            number_format($reclamation->getSentimentScore(), 2),
            nl2br($reclamation->getDescription()),
            $this->generateAdminUrl($reclamation->getId())
        );
    }

    private function generateAdminUrl(int $id): string
    {
        return $this->urlGenerator->generate(
            'admin_reclamation_reponses', 
            ['id' => $id],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}