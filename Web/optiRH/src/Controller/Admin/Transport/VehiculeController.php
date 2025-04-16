<?php

namespace App\Controller\Admin\Transport;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Transport\Vehicule;
use App\Form\Transport\VehiculeType;
use App\Entity\Transport\Trajet;
use App\Repository\Transport\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/transport/vehicule')]
class VehiculeController extends AbstractController
{
    #[Route('/new/{trajet_id}', name: 'app_transport_vehicule_new', methods: ['GET', 'POST'])]
public function new(
    Request $request, 
    EntityManagerInterface $entityManager, 
    int $trajet_id
): Response {
    $vehicule = new Vehicule();
    $form = $this->createForm(VehiculeType::class, $vehicule);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $trajet = $entityManager->getRepository(Trajet::class)->find($trajet_id);
        
        if (!$trajet) {
            throw $this->createNotFoundException('Trajet non trouvé');
        }
        
        $vehicule->setTrajet($trajet);
        $entityManager->persist($vehicule);
        $entityManager->flush();

        $this->addFlash('success', 'Le véhicule a été créé avec succès!');
        return $this->redirectToRoute('app_transport_trajet_vehicules', [
            'id' => $trajet_id,
            'created' => 1,
            'vehicule_id' => $vehicule->getId(),
            'vehicule_type' => $vehicule->getType()
        ]);
            }

    return $this->render('Transport/newVehicule.html.twig', [
        'vehicule' => $vehicule,
        'form' => $form->createView(),
        'trajet_id' => $trajet_id,
    ]);
}

#[Route('/{id}/edit', name: 'app_transport_vehicule_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Vehicule $vehicule, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(VehiculeType::class, $vehicule);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();
        
        // Ajout d'un paramètre dans l'URL pour indiquer que la modification a réussi
        return $this->redirectToRoute('app_transport_trajet_vehicules', [
            'id' => $vehicule->getTrajet()->getId(),
            'modified' => '1',
            'vehicule_id' => $vehicule->getId()
        ], Response::HTTP_SEE_OTHER);
    }

    return $this->render('Transport/editVehicule.html.twig', [
        'vehicule' => $vehicule,
        'form' => $form->createView(),
    ]);
}

    #[Route('/{id}', name: 'app_transport_vehicule_delete', methods: ['POST'])]
public function delete(Request $request, Vehicule $vehicule, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete'.$vehicule->getId(), $request->request->get('_token'))) {
        $entityManager->remove($vehicule);
        $entityManager->flush();
        
        // Redirection avec paramètre de succès
        return $this->redirectToRoute('app_transport_trajet_vehicules', [
            'id' => $vehicule->getTrajet()->getId(),
            'deleted' => 1
        ], Response::HTTP_SEE_OTHER);
    }

    // Gestion d'erreur CSRF
    return $this->redirectToRoute('app_transport_trajet_vehicules', [
        'id' => $vehicule->getTrajet()->getId()
    ], Response::HTTP_SEE_OTHER);
}


    #[Route('/vehicule/{id}/reservations', name: 'app_transport_vehicule_reservations', methods: ['GET'])]
public function reservations(Vehicule $vehicule): Response
{
    return $this->render('Transport/vehicule_reservations.html.twig', [
        'vehicule' => $vehicule,
        'reservations' => $vehicule->getReservations(),
    ]);
}


}