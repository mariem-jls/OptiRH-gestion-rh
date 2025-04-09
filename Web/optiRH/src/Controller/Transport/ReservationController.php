<?php
namespace App\Controller\Transport;
use App\Entity\Users\Users;
use App\Entity\Transport\ReservationTrajet;
use App\Entity\Transport\Vehicule;
use App\Repository\Transport\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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

        // Utilisez la méthode qui accepte les strings (départ/arrivée)
        $vehicules = $vehiculeRepo->findAvailableByDepartureArrival($depart, $arrive);

        return $this->render('transport/reservation/_results.html.twig', [
            'vehicules' => $vehicules
        ]);
    }

    #[Route('/reserve/{id}', name: 'app_transport_reservation_reserve', methods: ['POST'])]
public function reserve(Vehicule $vehicule, EntityManagerInterface $em, VehiculeRepository $vehiculeRepo): Response
{
    try {
        // Remplacez $this->getUser() par un utilisateur statique (ID = 1)
        $user = $em->getRepository(Users::class)->find(1);
        
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Utilisateur introuvable'], 404);
        }

        if ($vehicule->getNbrplace() <= 0) {
            return $this->json(['success' => false, 'message' => 'Plus de places disponibles'], 400);
        }

        // Créez la réservation
        $reservation = new ReservationTrajet();
        $reservation
            ->setVehicule($vehicule)
            ->setUser($user) // Utilisateur statique (ID = 1)
            ->setTrajet($vehicule->getTrajet())
            ->setDisponibilite('Confirmé');

        // Décrémentez le nombre de places
        $vehicule->setNbrplace($vehicule->getNbrplace() - 1);
        
        if ($vehicule->getNbrplace() <= 0) {
            $vehicule->setDisponibilite('Indisponible');
        }

        $em->persist($reservation);
        $em->flush();

        return $this->json([
            'success' => true,
            'newPlaces' => $vehicule->getNbrplace(),
            'newStatus' => $vehicule->getDisponibilite()
        ]);

    } catch (\Exception $e) {
        return $this->json([
            'success' => false,
            'message' => 'Erreur serveur : ' . $e->getMessage()
        ], 500);
    }
}
}