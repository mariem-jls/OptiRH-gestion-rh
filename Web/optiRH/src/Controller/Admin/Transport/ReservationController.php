<?php
namespace App\Controller\Admin\Transport;
use App\Entity\User;
use App\Entity\Transport\Vehicule;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Transport\ReservationTrajet;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Transport\VehiculeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\DBAL\LockMode;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Service\PdfGenerator;
use App\Repository\Transport\ReservationTrajetRepository;

#[Route('/transport/reservation')]
class ReservationController extends AbstractController
{
    #[Route('/', name: 'app_transport_reservation_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('transport/reservation/index.html.twig');
    }

    #[Route('/search', name: 'app_transport_reservation_search', methods: ['GET'])]
public function search(Request $request, VehiculeRepository $vehiculeRepo): Response
{
    $depart = $request->query->get('depart');
    $arrive = $request->query->get('arrive');

    // Si aucun critère n'est fourni, retourner une liste vide
    if (empty($depart) && empty($arrive)) {
        return $this->render('transport/reservation/_results.html.twig', [
            'vehicules' => [],
            'show_map' => false // Nouveau paramètre

        ]);
    }

    // Utilisez la méthode modifiée qui accepte des critères partiels
    $vehicules = $vehiculeRepo->findByDepartureOrArrival($depart, $arrive);
    return $this->render('transport/reservation/_results.html.twig', [
        'vehicules' => $vehicules,
        'show_map' => true, // Activer la carte
        'search_depart' => $depart, // Transmettre les critères
        'search_arrive' => $arrive
    ]);

}


    #[Route('/reserve/{id}', name: 'app_transport_reservation_reserve', methods: ['POST'])]
    public function reserve(
        Vehicule $vehicule,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        Request $request,
        MailerInterface $mailer,
    ): Response {
        try {
            $logger->debug('Tentative de réservation pour véhicule: '.$vehicule->getId());
            
            $user = $this->getUser();
            if (!$user instanceof User) {
                $this->addFlash('error', 'Utilisateur non valide');
                return $this->redirectToRoute('app_login');
            }
    
            $em->beginTransaction();
            $vehicule = $em->find(Vehicule::class, $vehicule->getId(), LockMode::PESSIMISTIC_WRITE);
    
            if (!$vehicule) {
                $em->rollback();
                $this->addFlash('error', 'Véhicule non trouvé');
                return $this->redirectToRoute('app_transport_reservation_index');
            }
    
            if ($vehicule->getNbrplace() <= 0) {
                $em->rollback();
                $this->addFlash('error', 'Plus de places disponibles');
                return $this->redirectToRoute('app_transport_reservation_index');
            }
    
            // Créez la réservation
            $reservation = new ReservationTrajet();
            $reservation
                ->setVehicule($vehicule)
                ->setUser($user)
                ->setTrajet($vehicule->getTrajet())
                ->setDisponibilite('Confirmé');
    
            $vehicule->setNbrplace($vehicule->getNbrplace() - 1);
            
            $em->persist($reservation);
            $em->flush();
            $em->commit();

            // Envoi de l'email de confirmation
        $email = (new Email())
        ->from('azettt532@gmail.com') // Utilisez l'email configuré dans MAILER_DSN
        ->to($user->getEmail()) // Assurez-vous que votre entité User a une méthode getEmail()
        ->subject('Confirmation de votre réservation')
        ->html($this->renderView(
            'transport/reservation/reservation_confirmation.html.twig',
            [
                'user' => $user,
                'reservation' => $reservation,
                'vehicule' => $vehicule,
                'trajet' => $vehicule->getTrajet()
            ]
        ));

    $mailer->send($email);
    
            $this->addFlash('success', 'Réservation confirmée. Un email de confirmation vous a été envoyé.');
            return $this->redirectToRoute('app_transport_reservation_index');
    
        
                
                
                
        } catch (\Exception $e) {
            if ($em->isOpen() && $em->getConnection()->isTransactionActive()) {
                $em->rollback();
            }
            $logger->error('ERREUR RESERVATION: '.$e->getMessage());
            
            // Laissez Symfony gérer l'exception (écran rouge en dev)
            throw $e;
        }
        
    }

    
    #[Route('/mes-reservations', name: 'app_transport_reservation_list', methods: ['GET'])]
public function userReservations(EntityManagerInterface $em): Response
{
    $user = $this->getUser();
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    // Récupérer les réservations directement depuis le repository
    $reservations = $em->getRepository(ReservationTrajet::class)
        ->findBy(['user' => $user->getId()]);

    return $this->render('transport/reservation/_reservations_list.html.twig', [
        'reservations' => $reservations
    ]);
}

#[Route('/{id}/delete', name: 'app_transport_reservation_delete', methods: ['POST'])]
public function deleteReservation(ReservationTrajet $reservation, EntityManagerInterface $em): JsonResponse
{
    try {
        $em->remove($reservation);
        $em->flush();
        
        // Incrémente le nombre de places du véhicule
        $vehicule = $reservation->getVehicule();
        $vehicule->setNbrplace($vehicule->getNbrplace() + 1);
        $em->flush();

        return $this->json(['success' => true]);
    } catch (\Exception $e) {
        return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}



#[Route('/api/trajets', name: 'app_transport_reservation_api_trajets', methods: ['GET'])]
public function getTrajetsApi(Request $request, VehiculeRepository $vehiculeRepo): JsonResponse
{
    $depart = $request->query->get('depart');
    $arrive = $request->query->get('arrive');

    $vehicules = $vehiculeRepo->findByDepartureOrArrival($depart, $arrive);

    $trajets = [];
    foreach ($vehicules as $vehicule) {
        $trajets[] = [
            'id' => $vehicule->getId(),
            'type' => $vehicule->getType(),
            'depart' => $vehicule->getTrajet()->getDepart(),
            'arrive' => $vehicule->getTrajet()->getArrive(),
            'places' => $vehicule->getNbrplace(),
            'disponibilite' => $vehicule->getDisponibilite()
        ];
    }

    return $this->json([
        'trajets' => $trajets,
        'search' => [
            'depart' => $depart,
            'arrive' => $arrive
        ]
    ]);
}  


#[Route('/reservations/pdf', name: 'app_reservations_pdf')]
public function generatePdf(ReservationTrajetRepository $reservationRepo, PdfGenerator $pdfGenerator): Response
{
    $reservations = $reservationRepo->findAll();
    
    $pdfContent = $pdfGenerator->generateReservationsPdf(
        $reservations, 
        'transport/reservation/pdf.html.twig'
    );
    
    return new Response(
        $pdfContent,
        Response::HTTP_OK,
        [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reservations_'.date('Y-m-d').'.pdf"'
        ]
    );
}



}