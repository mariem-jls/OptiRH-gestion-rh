<?php
namespace App\Controller\Admin\Evenement;

use App\Repository\Evenement\FavorisEvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Evenement\FavorisEvenement; // Assurez-vous d'avoir l'entité FavorisEvenement
use App\Entity\User; // Assurez-vous d'avoir l'entité User
use App\Entity\Evenement\Evenement; // Assurez-vous d'avoir l'entité Evenement
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class FavoriController extends AbstractController
{
    #[Route('/ajouter_favori', name: 'ajouter_favori', methods: ['POST'])]
    public function gererFavori(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
    
        if (!isset($data['event_id']) || !isset($data['user_id'])) {
            return new JsonResponse(['success' => false, 'message' => 'Données manquantes'], 400);
        }
    
        $eventId = $data['event_id'];
        $userId = $data['user_id'];
    
        $user = $em->getRepository(User::class)->find($userId);
        $event = $em->getRepository(Evenement::class)->find($eventId);
    
        if (!$user || !$event) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur ou événement introuvable'], 404);
        }
    
        $favoriRepository = $em->getRepository(FavorisEvenement::class);
        $existingFavori = $favoriRepository->findOneBy(['id_user' => $user, 'id_evenement' => $event]);
    
        if ($existingFavori) {
            // Le favori existe déjà, donc on le supprime
            $em->remove($existingFavori);
            $em->flush();
            return new JsonResponse(['success' => true, 'action' => 'removed']);
        } else {
            // Le favori n'existe pas, donc on l'ajoute
            $favori = new FavorisEvenement();
            $favori->setIdUser($user);
            $favori->setIdEvenement($event);
            $em->persist($favori);
            $em->flush();
            return new JsonResponse(['success' => true, 'action' => 'added']);
        }
    }


    



}
