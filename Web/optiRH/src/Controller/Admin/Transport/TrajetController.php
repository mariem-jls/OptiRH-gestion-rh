<?php
namespace App\Controller\Admin\Transport;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Entity\Transport\Trajet;
use App\Form\Transport\TrajetType;
use App\Repository\Transport\TrajetRepository;
use App\Repository\Transport\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/transport/trajet')]
class TrajetController extends AbstractController
{
    #[Route('/', name: 'app_transport_trajet_index', methods: ['GET'])]
    public function index(TrajetRepository $trajetRepository): Response
    {
        return $this->render('Transport/indexTrajet.html.twig', [
            'trajets' => $trajetRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_transport_trajet_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $trajet = new Trajet();
        $form = $this->createForm(TrajetType::class, $trajet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($trajet);
            $entityManager->flush();

            return $this->redirectToRoute('app_transport_trajet_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('Transport/newTrajet.html.twig', [
            'trajet' => $trajet,
            'form' => $form->createView(),
        ]);
    }


    #[Route('/{id}/edit', name: 'app_transport_trajet_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Trajet $trajet, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(TrajetType::class, $trajet);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();
        
        // Ajout d'un paramètre dans l'URL pour indiquer que la modification a réussi
        return $this->redirectToRoute('app_transport_trajet_index', [
            'modified' => '1',
            'id' => $trajet->getId()
        ], Response::HTTP_SEE_OTHER);
    }

    return $this->render('Transport/editTrajet.html.twig', [
        'trajet' => $trajet,
        'form' => $form->createView(),
    ]);
}


#[Route('/{id}', name: 'app_transport_trajet_delete', methods: ['POST'])]
public function delete(Request $request, Trajet $trajet, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete'.$trajet->getId(), $request->request->get('_token'))) {
        $entityManager->remove($trajet);
        $entityManager->flush();
        
        // Ajouter un paramètre dans l'URL pour la confirmation
        return $this->redirectToRoute('app_transport_trajet_index', [
            'deleted' => '1',
            'type' => $trajet->getType()
        ], Response::HTTP_SEE_OTHER);
    }

    return $this->redirectToRoute('app_transport_trajet_index', [], Response::HTTP_SEE_OTHER);
}


    #[Route('/{id}', name: 'app_transport_trajet_show', methods: ['GET'])]
public function show(Trajet $trajet): Response
{
    return $this->render('transport/trajet/show.html.twig', [
        'trajet' => $trajet,
    ]);
}


#[Route('/{id}/vehicules', name: 'app_transport_trajet_vehicules', methods: ['GET'])]
public function vehicules(Trajet $trajet, VehiculeRepository $vehiculeRepository): Response
{
    return $this->render('Transport/vehicules.html.twig', [
        'trajet' => $trajet,
        'vehicules' => $vehiculeRepository->findBy(['trajet' => $trajet]),
    ]);
}


#[Route('/stats/reservations', name: 'app_transport_trajet_stats', methods: ['GET'])]
public function stats(TrajetRepository $trajetRepository): JsonResponse
{
    $stats = $trajetRepository->getReservationStatsByVehicleType();
    return $this->json($stats);
}

}