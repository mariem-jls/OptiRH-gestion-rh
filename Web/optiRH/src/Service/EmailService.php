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
    public function sendInterviewConfirmationEmail(
        string $toEmail,
        string $candidateName,
        string $jobTitle,
        \DateTimeInterface $interviewDateTime,
        string $googleMeetLink
    ): void {
        $email = (new Email())
            ->from('no-reply@optirh.com')
            ->to($toEmail)
            ->subject('Confirmation de votre entretien pour ' . $jobTitle)
            ->text(sprintf(
                "Bonjour %s,\n\nVotre entretien est confirmé pour le %s.\nRejoignez la réunion : %s\n\nCordialement,\nÉquipe Recrutement",
                $candidateName,
                $interviewDateTime->format('d F Y à H:i'),
                $googleMeetLink
            ))
            ->html($this->twig->render('emails/interview_confirmation.html.twig', [
                'candidateName' => $candidateName,
                'jobTitle' => $jobTitle,
                'interviewDate' => $interviewDateTime->format('d F Y à H:i'),
                'googleMeetLink' => $googleMeetLink,
            ]));

        $this->mailer->send($email);
    }
    public function sendInterviewCancellationEmail(
        string $to,
        string $nomComplet,
        string $poste,
        \DateTimeInterface $dateTime
    ): void {
        try {
            $email = (new Email())
                ->from('no-reply@optirh.com')
                ->to($to)
                ->subject('Annulation de votre entretien')
                ->html($this->twig->render('emails/interview_cancellation.html.twig', [
                    'nomComplet' => $nomComplet,
                    'poste' => $poste,
                    'dateTime' => $dateTime,
                ]));

            $this->mailer->send($email);
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de l\'envoi de l\'email d\'annulation: ' . $e->getMessage());
        }
    }
}