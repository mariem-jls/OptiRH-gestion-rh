<?php
// src/Controller/Admin/Reclamation/ReclamationController.php

namespace App\Controller\Admin\Reclamation;

use App\Entity\Reclamation;
use App\Entity\Reponse;
use App\Entity\ReclamationArchive; // Ajout pour l'historique
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
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Mailer\MailerInterface; // <-- Ajouté ici
use Symfony\Component\Mime\Email;


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

    #[Route('/reclamations/pdf', name: 'admin_reclamations_pdf', methods: ['GET'])]
    public function generatePdf(EntityManagerInterface $em): Response
    {
        $reclamations = $em->getRepository(Reclamation::class)->findAll();
        
        // Configure Dompdf selon vos besoins
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        // Générer le HTML pour le PDF
        $html = $this->renderView('reclamation/pdf_list.html.twig', [
            'reclamations' => $reclamations,
            'title' => 'Liste des réclamations'
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Générer le PDF et le renvoyer comme réponse
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="reclamations.pdf"'
            ]
        );
    }
    
    #[Route('/reclamations/archive', name: 'admin_reclamations_archive', methods: ['GET'])]
    public function listArchive(EntityManagerInterface $em): Response
    {
        $archives = $em->getRepository(ReclamationArchive::class)->findAll();

        return $this->render('reclamation/archive_list.html.twig', [
            'archives' => $archives,
        ]);
    }
    
    #[Route('/reclamations/archive/pdf', name: 'admin_reclamations_archive_pdf', methods: ['GET'])]
    public function generateArchivePdf(EntityManagerInterface $em): Response
    {
        $archives = $em->getRepository(ReclamationArchive::class)->findAll();
        
        // Configure Dompdf selon vos besoins
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        // Générer le HTML pour le PDF
        $html = $this->renderView('reclamation/pdf_archive_list.html.twig', [
            'archives' => $archives,
            'title' => 'Historique des réclamations supprimées'
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Générer le PDF et le renvoyer comme réponse
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="reclamations-archive.pdf"'
            ]
        );
    }

    #[Route('/reclamation/{id}/reponses', name: 'admin_reclamation_reponses', methods: ['GET', 'POST'])]
    public function reponses(Reclamation $reclamation, Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
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

            // Envoi de l'email à l'employeur
            $employeur = $reclamation->getUtilisateur();
            if ($employeur && $employeur->getEmail()) {
                try {
                    $email = (new Email())
                        ->from('no-reply@votre-domaine.com')
                        ->to($employeur->getEmail())
                        ->subject('Nouvelle réponse à votre réclamation')
                        ->html($this->renderView(
                            'reclamation/nouvelle_reponse.html.twig',
                            [
                                'reclamation' => $reclamation,
                                'reponse' => $reponse,
                                'employeur' => $employeur
                            ]
                        ));

                    $mailer->send($email);
                } catch (\Exception $e) {
                    // Vous pouvez logger l'erreur si vous le souhaitez
                    $this->addFlash('warning', 'La réponse a été enregistrée mais l\'email n\'a pas pu être envoyé.');
                }
            }

            $this->addFlash('success', 'Réponse publiée avec succès !');
            return $this->redirectToRoute('admin_reclamation_reponses', ['id' => $reclamation->getId()]);
        }

        return $this->render('reclamation/reponses.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form->createView(),
            'can_edit' => false
        ]);
    }



    #[Route('/reclamation/{id}/qr-code', name: 'admin_reclamation_qr_code', methods: ['GET'])]
    public function generateQrCode(Reclamation $reclamation): Response
    {
        // Préparation des données de la réclamation à inclure dans le QR code
        $reclamationData = [
            'id' => $reclamation->getId(),
            'type' => $reclamation->getType(),
            'status' => $reclamation->getStatus(),
            'description' => $reclamation->getDescription(),
            'date' => $reclamation->getDate()->format('Y-m-d H:i:s'),
            'utilisateur' => $reclamation->getUtilisateur()->getNom()
        ];
        
        // Conversion des données en JSON pour le QR code
        $dataString = json_encode($reclamationData, JSON_UNESCAPED_UNICODE);
        
        // Création du QR code avec les données
        $qrCode = new QrCode($dataString);
        
        // Création du writer et génération de l'image
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        // Retourne l'image générée
        return new Response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => 'inline; filename="reclamation-details.png"'
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