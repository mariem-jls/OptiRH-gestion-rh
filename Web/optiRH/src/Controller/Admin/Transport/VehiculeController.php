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

        $this->addFlash('success', 'Véhicule créé avec succès');
        return $this->redirectToRoute('app_transport_trajet_vehicules', ['id' => $trajet_id]);
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

            return $this->redirectToRoute('app_transport_trajet_vehicules', ['id' => $vehicule->getTrajet()->getId()]);
        }

        return $this->render('Transport/editVehicule.html.twig', [
            'vehicule' => $vehicule,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_transport_vehicule_delete', methods: ['POST'])]
    public function delete(Request $request, Vehicule $vehicule, EntityManagerInterface $entityManager): Response
    {
        $trajetId = $vehicule->getTrajet()->getId();
        
        if ($this->isCsrfTokenValid('delete'.$vehicule->getId(), $request->request->get('_token'))) {
            $entityManager->remove($vehicule);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_transport_trajet_vehicules', ['id' => $trajetId]);
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