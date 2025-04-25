<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailService
{
    private $mailer;
    private $twig;

    public function __construct(MailerInterface $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function sendApplicationConfirmationEmail(string $toEmail, string $candidateName, string $jobTitle): void
    {
        $email = (new Email())
            ->from('no-reply@optirh.com')
            ->to($toEmail)
            ->subject('Confirmation de votre candidature - ' . $jobTitle)
            ->text('Merci pour votre candidature, ' . $candidateName . '!')
            ->html($this->twig->render('emails/application_confirmation.html.twig', [
                'candidateName' => $candidateName,
                'jobTitle' => $jobTitle,
            ]));

        $this->mailer->send($email);
    }
}