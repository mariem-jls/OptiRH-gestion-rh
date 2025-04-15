<?php
// src/Controller/Admin/Reclamation/ReclamationController.php

namespace App\Controller\Admin\Reclamation;

use App\Entity\Reclamation;
use App\Entity\Reponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class ReclamationController extends AbstractController
{
    #[Route('/reclamations', name: 'admin_reclamations', methods: ['GET'])]
    public function list(EntityManagerInterface $em): Response
    {
        $reclamations = $em->getRepository(Reclamation::class)->findAll();
        return $this->render('reclamation/list.html.twig', [
            'reclamations' => $reclamations,
        ]);
    }

    #[Route('/reclamation/{id}/reponses', name: 'admin_reclamation_reponses', methods: ['GET', 'POST'])]
    public function reponses(Reclamation $reclamation, Request $request, EntityManagerInterface $em): Response
    {
        $reponse = new Reponse();
        $form = $this->createFormBuilder($reponse)
            ->add('description', TextareaType::class, [
                'label' => 'Votre réponse',
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-control',
                    'placeholder' => 'Écrivez votre réponse ici...'
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Publier la réponse',
                'attr' => ['class' => 'btn btn-primary mt-3']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reponse->setReclamation($reclamation);
            $reponse->setDate(new \DateTime());
            
            if ($reclamation->getStatus() === Reclamation::STATUS_PENDING) {
                $reclamation->setStatus(Reclamation::STATUS_IN_PROGRESS);
            }
            
            $em->persist($reponse);
            $em->flush();
            
            $this->addFlash('success', 'Réponse publiée avec succès !');
            return $this->redirectToRoute('admin_reclamation_reponses', ['id' => $reclamation->getId()]);
        }

        return $this->render('reclamation/reponses.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form->createView(),
            'can_edit' => false // Empêche l'édition des réponses
        ]);
    }

    #[Route('/reclamation/{id}/update-status', name: 'admin_update_status', methods: ['POST'])]
    public function updateStatus(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        $status = $request->request->get('status');
        
        if (in_array($status, [
            Reclamation::STATUS_PENDING,
            Reclamation::STATUS_IN_PROGRESS, 
            Reclamation::STATUS_RESOLVED
        ])) {
            $reclamation->setStatus($status);
            $em->flush();
            $this->addFlash('success', 'Statut mis à jour avec succès');
        } else {
            $this->addFlash('error', 'Statut invalide');
        }

        return $this->redirectToRoute('admin_reclamations');
    }
    #[Route('/reponse/{id}/edit', name: 'admin_reponse_edit', methods: ['GET', 'POST'])]
    public function editReponse(Request $request, Reponse $reponse, EntityManagerInterface $em): Response
    {
        // Bloquer si la réponse a une notation
        if ($reponse->getRating() > 0) {
            $this->addFlash('error', 'Impossible de modifier une réponse notée');
            return $this->redirectToRoute('admin_reclamation_reponses', ['id' => $reponse->getReclamation()->getId()]);
        }

        $form = $this->createFormBuilder($reponse)
            ->add('description', TextareaType::class, [
                'attr' => ['rows' => 5, 'minlength' => 5],
        
            ])
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Réponse modifiée');
            return $this->redirectToRoute('admin_reclamation_reponses', ['id' => $reponse->getReclamation()->getId()]);
        }

        return $this->render('reponse/edit.html.twig', [
            'form' => $form->createView(),
            'reponse' => $reponse
        ]);
    }

    #[Route('/reponse/{id}/delete', name: 'admin_reponse_delete', methods: ['POST'])]
    public function deleteReponse(Request $request, Reponse $reponse, EntityManagerInterface $em): Response
    {
        // Bloquer si la réponse a une notation
        if ($reponse->getRating() > 0) {
            $this->addflash('error', 'Impossible de supprimer une réponse notée');
            return $this->redirectToRoute('admin_reclamation_reponses', ['id' => $reponse->getReclamation()->getId()]);
        }

        if ($this->isCsrfTokenValid('delete'.$reponse->getId(), $request->request->get('_token'))) {
            $em->remove($reponse);
            $em->flush();
            $this->addFlash('success', 'Réponse supprimée');
        }

        return $this->redirectToRoute('admin_reclamation_reponses', ['id' => $reponse->getReclamation()->getId()]);
    }
}