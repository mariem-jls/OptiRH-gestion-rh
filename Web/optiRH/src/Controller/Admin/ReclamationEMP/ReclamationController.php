<?php
// src/Controller/FrontOffice/Reclamation/ReclamationController.php

namespace App\Controller\Admin\ReclamationEMP;

use App\Entity\Reponse;
use App\Entity\Reclamation;
use App\Form\ReclamationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/list')]
class ReclamationController extends AbstractController
{
    #[Route('/', name: 'front_home')]
    public function index(): Response
    {
        return $this->redirectToRoute('front_reclamations');
    }

    #[Route('/reclamations', name: 'front_reclamations')]
    #[IsGranted('ROLE_USER')]
    public function list(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $reclamations = $em->getRepository(Reclamation::class)->findBy(['utilisateur' => $user]);

        return $this->render('front/reclamation/list.html.twig', [
            'reclamations' => $reclamations,
        ]);
    }

    #[Route('/reclamation/add', name: 'front_add_reclamation')]
    #[IsGranted('ROLE_USER')]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation, ['is_admin' => false]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reclamation->setStatus(Reclamation::STATUS_PENDING);
            $reclamation->setUtilisateur($this->getUser());
            $reclamation->setDate(new \DateTime());

            $em->persist($reclamation);
            $em->flush();

            $this->addFlash('success', 'Votre réclamation a été enregistrée avec succès.');
            return $this->redirectToRoute('front_reclamations');
        }

        return $this->render('front/reclamation/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reclamation/{id}/edit', name: 'front_edit_reclamation')]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        if ($reclamation->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($reclamation->getReponses()->count() > 0) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier une réclamation qui a déjà une réponse.');
            return $this->redirectToRoute('front_reclamations');
        }

        $form = $this->createForm(ReclamationType::class, $reclamation, ['is_admin' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Réclamation modifiée avec succès.');
            return $this->redirectToRoute('front_reclamations');
        }

        return $this->render('front/reclamation/edit.html.twig', [
            'form' => $form->createView(),
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/reclamation/{id}/delete', name: 'front_delete_reclamation', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        // Vérifier que la réclamation appartient à l'utilisateur
        if ($reclamation->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
    
        // Empêcher la suppression si la réclamation a des réponses
        if ($reclamation->getReponses()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer une réclamation avec des réponses');
            return $this->redirectToRoute('front_reclamations');
        }
    
        if ($this->isCsrfTokenValid('delete'.$reclamation->getId(), $request->request->get('_token'))) {
            $em->remove($reclamation);
            $em->flush();
            $this->addFlash('success', 'Réclamation supprimée avec succès.');
        }
    
        return $this->redirectToRoute('front_reclamations');
    }

    #[Route('/reclamation/{id}/reponses', name: 'front_reclamation_reponses')]
    #[IsGranted('ROLE_USER')]
    public function reponses(Reclamation $reclamation): Response
    {
        if ($reclamation->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('front/reclamation/reponses.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/reponse/{id}/rate', name: 'front_rate_reponse', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function rateReponse(Request $request, Reponse $reponse, EntityManagerInterface $em): Response
    {
        $reclamation = $reponse->getReclamation();

        if ($reclamation->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $rating = $request->request->get('rating');

        if ($rating >= 1 && $rating <= 5) {
            $reponse->setRating((int)$rating);
            $reclamation->setStatus(Reclamation::STATUS_RESOLVED);
            
            $em->flush();
            $this->addFlash('success', 'Merci pour votre évaluation !');
        } else {
            $this->addFlash('error', 'Veuillez sélectionner une note valide.');
        }

        return $this->redirectToRoute('front_reclamation_reponses', ['id' => $reclamation->getId()]);
    }
}