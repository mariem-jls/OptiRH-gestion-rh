<?php

namespace App\Controller\FrontOffice\Demande;

use App\Entity\Demande;
use App\Form\DemandeType;
use App\Repository\DemandeRepository;
use App\Repository\OffreRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Psr\Log\LoggerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/demande')]
class DemandeController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    #[Route('/', name: 'app_demande_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $demandes = $entityManager->getRepository(Demande::class)->findAll();
        $uploadsDir = $this->getParameter('uploads_directory');

        foreach ($demandes as $demande) {
            if ($demande->getFichierPieceJointe()) {
                $demande->fileExists = file_exists($uploadsDir.'/'.$demande->getFichierPieceJointe());
            }
        }

        return $this->render('demande/index.html.twig', [
            'demandes' => $demandes
        ]);
    }

    #[Route('/demande/new/{offre_id}', name: 'app_demande_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        DemandeRepository $demandeRepository,
        OffreRepository $offreRepository,
        int $offre_id,
        EmailService $emailService,
        LoggerInterface $logger
    ): Response {
        $offre = $offreRepository->find($offre_id);
        if (!$offre) {
            throw $this->createNotFoundException('Offre introuvable');
        }

        $demande = new Demande();
        $demande->setOffre($offre);

        $form = $this->createForm(DemandeType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $logger->info('Formulaire soumis et valide');

            if (!$demande->getOffre()) {
                $this->addFlash('error', 'Aucune offre associée');
                $logger->info('Aucune offre associée');
                return $this->redirectToRoute('app_front_active');
            }

            $file = $form->get('fichierPieceJointe')->getData();
            if ($file) {
                $originalFileName = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $fileName = $originalFileName . '_' . time() . '.' . $extension;

                try {
                    $file->move(
                        $this->getParameter('uploads_directory'),
                        $fileName
                    );
                    $demande->setFichierPieceJointe($fileName);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du fichier : ' . $e->getMessage());
                    $logger->error('Erreur lors de l\'upload du fichier : ' . $e->getMessage());
                    return $this->redirectToRoute('app_demande_new', ['offre_id' => $offre_id]);
                }
            }

            $demandeRepository->save($demande, true);
            $logger->info('Demande enregistrée avec succès');

            $this->addFlash('info', 'Demande enregistrée, envoi de l\'email en cours...');
            $logger->info('Tentative d\'envoi d\'email à : ' . $demande->getEmail(), [
                'candidateName' => $demande->getNomComplet(),
                'jobTitle' => $offre->getPoste(),
            ]);

            try {
                $emailService->sendApplicationConfirmationEmail(
                    $demande->getEmail(),
                    $demande->getNomComplet(),
                    $offre->getPoste()
                );
                $this->addFlash('success', 'Votre candidature a été soumise avec succès ! Un email de confirmation vous a été envoyé.');
                $logger->info('Email envoyé avec succès à : ' . $demande->getEmail());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Candidature soumise, mais l\'envoi de l\'email de confirmation a échoué : ' . $e->getMessage());
                $logger->error('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
            }

            $logger->info('Redirection vers app_front');
            return $this->redirectToRoute('app_front');
        }

        $logger->info('Formulaire non soumis ou non valide');
        return $this->render('demande/new.html.twig', [
            'form' => $form->createView(),
            'offre' => $offre
        ]);
    }

    #[Route('/{id}', name: 'app_demande_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(?Demande $demande): Response
    {
        $this->logger->debug('Attempting to show Demande', ['id' => $demande ? $demande->getId() : 'null']);
        if (!$demande) {
            $this->addFlash('danger', 'La demande spécifiée n\'existe pas.');
            return $this->redirectToRoute('admin_analyse_cv');
        }

        return $this->render('demande/show.html.twig', [
            'demande' => $demande,
            'interviews' => $demande->getInterviews(),
        ]);
    }
    #[Route('/{id}/pdf', name: 'app_demande_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function generatePdf(?Demande $demande): Response
    {
        $this->logger->debug('Generating PDF for Demande', ['id' => $demande ? $demande->getId() : 'null']);
        if (!$demande) {
            $this->addFlash('danger', 'La demande spécifiée n\'existe pas.');
            return $this->redirectToRoute('admin_analyse_cv');
        }

        // Configurer Dompdf avec les paramètres définis dans services.yaml
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('tempDir', $this->getParameter('pdf.temp_dir'));
        $options->set('fontDir', $this->getParameter('pdf.font_dir'));
        $options->set('fontCache', $this->getParameter('pdf.font_dir'));

        $dompdf = new Dompdf($options);

        // Rendre le contenu HTML de la demande
        $html = $this->renderView('demande/pdf.html.twig', [
            'demande' => $demande,
            'interviews' => $demande->getInterviews(),
        ]);

        // Charger le HTML dans Dompdf
        $dompdf->loadHtml($html);

        // Définir le format du papier (A4 par défaut)
        $dompdf->setPaper('A4', 'portrait');

        // Ajouter des métadonnées
        $dompdf->addInfo('Title', 'Détails de la demande #' . $demande->getId());
        $dompdf->addInfo('Author', 'OptiRH');
        $dompdf->addInfo('Subject', 'Demande d\'emploi');
        $dompdf->addInfo('Keywords', 'demande, emploi, recrutement');

        // Rendre le PDF
        try {
            $dompdf->render();
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate PDF', ['error' => $e->getMessage()]);
            throw $e;
        }

        // Générer un nom de fichier unique
        $filename = sprintf('demande_%s_%s.pdf', $demande->getId(), (new \DateTime())->format('Ymd_His'));

        // Envoyer le PDF en réponse
        $output = $dompdf->output();
        return new Response(
            $output,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"', // Défini manuellement
            ]
        );
    }
    #[Route('/{id}/edit', name: 'app_demande_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Demande $demande,
        DemandeRepository $demandeRepository
    ): Response {
        $form = $this->createForm(DemandeType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('fichierPieceJointe')->getData();

            if ($file) {
                $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $fileName = $originalFileName . '_' . time() . '.' . $extension;

                try {
                    $oldFile = $demande->getFichierPieceJointe();
                    if ($oldFile && file_exists($this->getParameter('uploads_directory') . '/' . $oldFile)) {
                        unlink($this->getParameter('uploads_directory') . '/' . $oldFile);
                    }

                    $file->move(
                        $this->getParameter('uploads_directory'),
                        $fileName
                    );
                    $demande->setFichierPieceJointe($fileName);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du fichier : ' . $e->getMessage());
                    return $this->redirectToRoute('app_demande_edit', ['id' => $demande->getId()]);
                }
            }

            $demandeRepository->save($demande, true);
            return $this->redirectToRoute('app_demande_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('demande/edit.html.twig', [
            'demande' => $demande,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_demande_delete', methods: ['POST'])]
    public function delete(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$demande->getId(), $request->request->get('_token'))) {
            $file = $demande->getFichierPieceJointe();
            if ($file && file_exists($this->getParameter('uploads_directory') . '/' . $file)) {
                unlink($this->getParameter('uploads_directory') . '/' . $file);
            }

            $entityManager->remove($demande);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_demande_index', [], Response::HTTP_SEE_OTHER);
    }
}