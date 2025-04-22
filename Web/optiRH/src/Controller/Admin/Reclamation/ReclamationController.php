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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Writer\PngWriter;

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
            'can_edit' => false
        ]);
    }

    #[Route('/reclamation/{id}/qr-code-mini', name: 'admin_reclamation_qr_code_mini', methods: ['GET'])]
    public function generateMiniQrCode(Reclamation $reclamation): Response
    {
        $url = $this->generateUrl(
            'admin_reclamation_reponses',
            ['id' => $reclamation->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $qrCode = new QrCode($url);
        $qrCode->setEncoding(new Encoding('UTF-8'));
        $qrCode->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh());
        $qrCode->setSize(100);
        $qrCode->setMargin(5);

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return new Response($result->getString(), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="qr-code-mini.png"',
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
        if ($reponse->getRating() > 0) {
            $this->addFlash('error', 'Impossible de modifier une réponse notée');
            return $this->redirectToRoute('admin_reclamation_reponses', ['id' => $reponse->getReclamation()->getId()]);
        }

        $form = $this->createFormBuilder($reponse)
            ->add('description', TextareaType::class, [
                'attr' => ['rows' => 5, 'minlength' => 5],
            ])
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
        if ($reponse->getRating() > 0) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Impossible de supprimer une réponse notée']);
            }
            $this->addFlash('error', 'Impossible de supprimer une réponse notée');
            return $this->redirectToRoute('admin_reclamation_reponses', ['id' => $reponse->getReclamation()->getId()]);
        }

        if ($this->isCsrfTokenValid('delete'.$reponse->getId(), $request->request->get('_token'))) {
            $em->remove($reponse);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'message' => 'Réponse supprimée avec succès']);
            }

            $this->addFlash('success', 'Réponse supprimée');
        }

        return $this->redirectToRoute('admin_reclamation_reponses', ['id' => $reponse->getReclamation()->getId()]);
    }
}
