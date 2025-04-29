<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use App\Entity\Transport\ReservationTrajet;

class ReservationMailer
{
    private $mailer;
    private $twig;
    private $senderEmail;

    public function __construct(MailerInterface $mailer, Environment $twig, string $senderEmail)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->senderEmail = $senderEmail;
    }

    public function sendConfirmationEmail(ReservationTrajet $reservation): void
    {
        $email = (new Email())
            ->from($this->senderEmail)
            ->to($reservation->getUser()->getEmail())
            ->subject('Confirmation de réservation')
            ->html($this->twig->render('emails/reservation_confirmation.html.twig', [
                'reservation' => $reservation,
                'user' => $reservation->getUser(),
                'vehicule' => $reservation->getVehicule(),
                'trajet' => $reservation->getTrajet()
            ]));

        $this->mailer->send($email);
    }
}