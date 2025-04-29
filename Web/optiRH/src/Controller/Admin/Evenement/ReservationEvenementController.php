<?php

namespace App\Controller\Admin\Evenement;

use App\Entity\Evenement\ReservationEvenement;
use App\Entity\User;
use App\Form\Evenement\ReservationEvenementType;
use App\Repository\Evenement\ReservationEvenementRepository;
use App\Service\TwilioService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Evenement\Evenement;
use Doctrine\ORM\EntityManagerInterface; 
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twilio\Rest\Client;

use Stripe\Checkout\Session;
use Stripe\Stripe;



#[Route('/reservation/evenement')]
class ReservationEvenementController extends AbstractController
{
    /*******Afficher toute les reservation******* */
    #[Route('/', name: 'app_reservation_evenement_index', methods: ['GET'])]
    public function index(ReservationEvenementRepository $reservationEvenementRepository): Response
    {
        return $this->render('reservation_evenement/index.html.twig', [
            'reservation_evenements' => $reservationEvenementRepository->findAll(),
        ]);
    }
    

    /*************Reservation+paiement****************/
    #[Route('/new/{id}', name: 'app_reservation_evenement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Evenement $evenement, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        $reservation = new ReservationEvenement();
        $reservation->setEvenement($evenement);
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $reservation->setFirstName($user->getNom());
        $reservation->setEmail($user->getEmail());
        $reservation->setUser($user);

        /*$existingReservation = $entityManager->getRepository(ReservationEvenement::class)
            ->findOneBy(['user' => $user, 'Evenement' => $evenement]);

        if ($existingReservation) {
            $this->addFlash('deja_reserve', 'Vous avez déjà réservé pour cet événement.');
            return $this->redirectToRoute('app_evenement_indexfront');
        }*/

        if ($evenement->getNbrPersonnes() == 0) {
            $this->addFlash('complet', 'Désolé, cet événement est complet.');
            return $this->redirectToRoute('app_evenement_indexfront');
        }

        $now = new \DateTime();
        if ($evenement->getDateDebut() <= $now) {
            $this->addFlash('date_passee', 'Désolé, l\'événement a déjà commencé.');
            return $this->redirectToRoute('app_evenement_indexfront');
        }

        $form = $this->createForm(ReservationEvenementType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Stocker les données de la réservation en session avant le paiement
            $reservationData = [
                'evenement_id' => $evenement->getId(),
                'user_id' => $user->getId(),
                'first_name' => $reservation->getFirstName(),
                'last_name' => $form->get('last_name')->getData(),
                'email' => $reservation->getEmail(),
                'telephone' => $form->get('telephone')->getData(),
            ];
            $session->set('reservation_data', $reservationData);

            // Rediriger vers la page de paiement Stripe
            return $this->redirectToRoute('app_reservation_evenement_payment', ['id' => $evenement->getId()]);
        }

        return $this->render('reservation_evenement/new.html.twig', [
            'form' => $form->createView(),
            'evenement' => $evenement,
        ]);
    }

    // Route pour afficher la page de paiement
    #[Route('/payment/{id}', name: 'app_reservation_evenement_payment')]public function payment(Evenement $evenement, SessionInterface $session, StripeClient $stripe): Response
    {
        $reservationData = $session->get('reservation_data');
        if (!$reservationData || $reservationData['evenement_id'] !== $evenement->getId()) {
            $this->addFlash('error', 'Informations de réservation invalides.');
            return $this->redirectToRoute('app_reservation_evenement_new', ['id' => $evenement->getId()]);
        }
    
        $prix = $evenement->getPrix() * 100; // Conversion en centimes
    
        $checkoutSession = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $evenement->getTitre(),
                        'description' => 'Réservation pour ' . $reservationData['first_name'] . ' ' . $reservationData['last_name'], // Affiche le nom
                    ],
                    'unit_amount' => $prix,
                ],
                'quantity' => 1,
            ]],
            'customer_email' => $reservationData['email'], // E-mail pré-rempli 
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_reservation_evenement_success', ['id' => $evenement->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('app_evenement_indexfront', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    
        return $this->redirect($checkoutSession->url);
    }


    /**Dtabase save************ */
    #[Route('/payment/success/{id}', name: 'app_reservation_evenement_success')]
    public function processPayment(Evenement $evenement, SessionInterface $session, EntityManagerInterface $entityManager, TwilioService $twilio): Response
    {
        $reservationData = $session->get('reservation_data');
        if (!$reservationData || $reservationData['evenement_id'] !== $evenement->getId()) {
            $this->addFlash('error', 'Informations de réservation invalides.');
            return $this->redirectToRoute('app_evenement_indexfront');
        }

        $reservation = new ReservationEvenement();
        $user = $entityManager->getRepository(User::class)->find($reservationData['user_id']);

        $reservation->setEvenement($evenement);
        $reservation->setUser($user);
        $reservation->setFirstName($reservationData['first_name']);
        $reservation->setLastName($reservationData['last_name']);
        $reservation->setEmail($reservationData['email']);
        $reservation->setTelephone($reservationData['telephone']);
        $reservation->setDateReservation(new \DateTimeImmutable());

        $evenement->decrementNbrPersonnes();

        $entityManager->persist($reservation);
        $entityManager->flush();

        $session->remove('reservation_data');

        /*$message = sprintf(
            "Bonjour %s, votre réservation pour '%s' le %s à %s est confirmée !",
            $reservation->getFirstName(),
            $evenement->getTitre(),
            $evenement->getDateDebut()->format('d/m/Y'),
            $evenement->getHeure()->format('H:i')
        );

        try {
            $twilio->sendSms($reservation->getTelephone(), $message);
        } catch (\Exception $e) {
            $this->addFlash('sms', "Erreur lors de l'envoi du SMS : " . $e->getMessage());
        }*/

        $this->addFlash('paiment', 'Paiement effectué avec succès ! Votre réservation est confirmée.');
        return $this->redirectToRoute('app_evenement_indexfront');
    }

     
    /**************Modifier une reservation************* */

    #[Route('/{id}/edit', name: 'app_reservation_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ReservationEvenement $reservationEvenement, ReservationEvenementRepository $reservationEvenementRepository): Response
    {
        $form = $this->createForm(ReservationEvenementType::class, $reservationEvenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try{
                $reservationEvenementRepository->save($reservationEvenement, true);
                $this->addFlash('modifiee', 'La réservation a été modifiée avec succès !');
                return $this->redirectToRoute('app_user_reservations', [], Response::HTTP_SEE_OTHER);

            }catch(\Exception $e){
                $this->addFlash('erreur', 'Une erreur est survenue lors de la modification de la réservation.');

            }
            

        }

        return $this->renderForm( 'reservation_evenement/edit.html.twig', [
            'reservation_evenement' => $reservationEvenement,
            'form' => $form,
        ]);
    }

    /**********Supprimer une reservation********* */
    #[Route('/{id}', name: 'app_reservation_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, ReservationEvenement $reservationEvenement, ReservationEvenementRepository $reservationEvenementRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservationEvenement->getId(), $request->request->get('_token'))) {
            $dateEvenement = $reservationEvenement->getEvenement()->getDateDebut();
            $now = new \DateTime();
            
            // Calculer la différence entre maintenant et la date de l'événement
            $diff = $now->diff($dateEvenement);
            
            $hoursLeft = ($diff->days * 24) + $diff->h;
            
            if ($dateEvenement > $now && $hoursLeft > 24) {
                $evenement = $reservationEvenement->getEvenement();
                $evenement->incrementNbrPersonnes(); // Appel de la méthode pour augmenter le nombre de places disponibles

                $reservationEvenementRepository->remove($reservationEvenement, true);
                $this->addFlash('réservation', 'La réservation a été supprimée avec succès.');
            } else {
                $this->addFlash('Impossibleréservation', 'Impossible de supprimer la réservation : l\'événement commence dans moins de 24 heures.');
            }
        } else {
            $this->addFlash('Impossibleréservation', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_user_reservations');
    }




    /************Afficher les resrvations pour un user******** */
     #[Route('/reservations/user', name: 'app_user_reservations')]
     public function showUserReservations(ReservationEvenementRepository $reservationEvenementRepository): Response
     {
         $user = $this->getUser();
     
         if (!$user) {
             return $this->redirectToRoute('app_login');
         }
     
         $reservations = $reservationEvenementRepository->findBy(['user' => $user]);
     
         
     
         return $this->render('reservation_evenement/Mes_Reservations.html.twig', [
             'reservations' => $reservations,
             
         ]);
     }

     
 
    
 
    // Controller:
#[Route('/reservationspdf/{id}', name: 'app_reservation_pdf')]
public function generatePdf(ReservationEvenement $reservation): Response
{
    $evenement = $reservation->getEvenement();

    // Générer le code-barres
    $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
    $barcode = $generator->getBarcode((string)$reservation->getId(), $generator::TYPE_CODE_128);

    $fouk=$this->getParameter('kernel.project_dir') . '/public/images/fouk.png';
    $foukBase64 = base64_encode(file_get_contents($fouk));

    $louta=$this->getParameter('kernel.project_dir') . '/public/images/louta.png';
    $loutaoBase64 = base64_encode(file_get_contents($louta));

    $lignePath = $this->getParameter('kernel.project_dir') . '/public/images/ligne.png';
    $ligneBase64 = base64_encode(file_get_contents($lignePath));
    // Charger le logo en base64
    $logoPath = $this->getParameter('kernel.project_dir') . '/public/images/logo-dark.png';
    $logoBase64 = base64_encode(file_get_contents($logoPath));

    $calendarIconPath = $this->getParameter('kernel.project_dir') . '/public/images/calendar.png';
    $calendarIconBase64 = base64_encode(file_get_contents($calendarIconPath));

    $clockIconPath = $this->getParameter('kernel.project_dir') . '/public/images/clock.png';
    $clockIconBase64 = base64_encode(file_get_contents($clockIconPath));

    $locationIconPath = $this->getParameter('kernel.project_dir') . '/public/images/location.png';
    $locationIconBase64 = base64_encode(file_get_contents($locationIconPath));

    // Charger les icônes SDG en base64
    $sdgIcons = [];
    foreach ([8, 9, 10, 12] as $num) {
        $path = $this->getParameter('kernel.project_dir') . "/public/images/$num.png";
        $sdgIcons[$num] = base64_encode(file_get_contents($path));
    }
    // Générer le HTML
    $html = $this->renderView('reservation_evenement/ticket.html.twig', [
        'reservation' => $reservation,
        'evenement' => $evenement,
        'barcode' => base64_encode($barcode),
        'logo' => $logoBase64,
        'calendar_icon' => $calendarIconBase64,
        'clock_icon' => $clockIconBase64,
        'location_icon' => $locationIconBase64,
        'sdg_icons' => $sdgIcons,
        'ligne' => $ligneBase64,
    ]);

    // Configuration de Dompdf
    $pdfOptions = new Options();
    $pdfOptions->set('defaultFont', 'Arial');
    $pdfOptions->set('isHtml5ParserEnabled', true);
    $pdfOptions->set('isPhpEnabled', true);
    $pdfOptions->set('isRemoteEnabled', true);
    $pdfOptions->set('dpi', 150);
    $pdfOptions->set('isFontSubsettingEnabled', true);

    $dompdf = new Dompdf($pdfOptions);
    $dompdf->loadHtml($html);
    // Définir la taille du papier sur A4 et l'orientation sur paysage
    $dompdf->setPaper('A4', 'landscape');

    // Générer le PDF
    $dompdf->render();

    return new Response(
        $dompdf->output(),
        200,
        [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="ticket_reservation.pdf"'
        ]
    );
}
      
      
 
     
     
}
