<?php
// src/Controller/ReclamationController.php
namespace App\Controller\backReclamation;

use App\Entity\Reclamation;
use App\Entity\Reponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class ReclamationController extends AbstractController
{
    #[Route('/admin/reclamations', name: 'admin_reclamations', methods: ['GET'])]
    public function list(EntityManagerInterface $em): Response
    {
        $reclamations = $em->getRepository(Reclamation::class)->findAll();
        return $this->render('reclamation/list.html.twig', [
            'reclamations' => $reclamations,
        ]);
    }

    #[Route('/admin/reclamation/{id}/delete', name: 'admin_reclamation_delete', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reclamation->getId(), $request->request->get('_token'))) {
            try {
                foreach ($reclamation->getReponses() as $reponse) {
                    $em->remove($reponse);
                }
                $em->remove($reclamation);
                $em->flush();
                $this->addFlash('success', 'Réclamation supprimée avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : '.$e->getMessage());
            }
        }
        return $this->redirectToRoute('admin_reclamations');
    }

    #[Route('/admin/reclamation/{id}/reponses', name: 'admin_reclamation_reponses', methods: ['GET', 'POST'])]
    public function reponses(Reclamation $reclamation, Request $request, EntityManagerInterface $em): Response
    {
        $reponse = new Reponse();
        $form = $this->createFormBuilder($reponse)
            ->add('description', TextareaType::class, [
                'label' => 'Votre réponse',
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-control',
                    'placeholder' => 'Écrivez votre réponse ici (minimum 5 caractères)...',
                    'minlength' => 5
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La réponse ne peut pas être vide']),
                    new Length([
                        'min' => 5,
                        'minMessage' => 'La réponse doit contenir au moins {{ limit }} caractères',
                        'max' => 5000,
                    ]),
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
            $em->persist($reponse);

            // Mise à jour automatique du statut si c'est la première réponse
            if ($reclamation->getReponses()->count() === 0) {
                $reclamation->setStatus(Reclamation::STATUS_IN_PROGRESS);
            }

            $em->flush();
            $this->addFlash('success', 'Réponse publiée avec succès !');
            return $this->redirectToRoute('admin_reclamation_reponses', ['id' => $reclamation->getId()]);
        }

        return $this->render('reclamation/reponses.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form->createView(),
        ]);
    }



    #[Route('/admin/reponse/{id}/delete', name: 'admin_reponse_delete', methods: ['POST'])]
    public function deleteReponse(Request $request, Reponse $reponse, EntityManagerInterface $em): Response
    {
        $reclamationId = $reponse->getReclamation()->getId();
        if ($this->isCsrfTokenValid('delete'.$reponse->getId(), $request->request->get('_token'))) {
            $em->remove($reponse);
            $em->flush();
            $this->addFlash('success', 'Réponse supprimée avec succès.');
        }
        return $this->redirectToRoute('admin_reclamation_reponses', ['id' => $reclamationId]);
    }
    // src/Controller/ReclamationController.php

    #[Route('/admin/reponse/{id}/edit', name: 'admin_reponse_edit', methods: ['GET', 'POST'])]
    public function editReponse(Request $request, Reponse $reponse, EntityManagerInterface $em): Response
    {
        $form = $this->createFormBuilder($reponse)
            ->add('description', TextareaType::class, [
                'label' => 'Modifier la réponse',
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-control',
                    'minlength' => 5
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La réponse ne peut pas être vide']),
                    new Length([
                        'min' => 5,
                        'minMessage' => 'La réponse doit contenir au moins {{ limit }} caractères',
                        'max' => 5000,
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer les modifications',
                'attr' => ['class' => 'btn btn-primary mt-3']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Réponse modifiée avec succès !');
            return $this->redirectToRoute('admin_reclamation_reponses', ['id' => $reponse->getReclamation()->getId()]);
        }

        return $this->render('reponse/edit.html.twig', [
            'form' => $form->createView(),
            'reponse' => $reponse,
        ]);
    }

}