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
            'vehicules' => []
        ]);
    }

    // Utilisez la méthode modifiée qui accepte des critères partiels
    $vehicules = $vehiculeRepo->findByDepartureOrArrival($depart, $arrive);

    return $this->render('transport/reservation/_results.html.twig', [
        'vehicules' => $vehicules
    ]);
}


    #[Route('/reserve/{id}', name: 'app_transport_reservation_reserve', methods: ['POST'])]
    public function reserve(
        Vehicule $vehicule,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        Request $request
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
    
            $this->addFlash('success', 'Réservation confirmée');
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
}