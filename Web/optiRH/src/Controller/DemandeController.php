<?php

namespace App\Controller;

use App\Entity\Demande;
use App\Form\DemandeType;
use App\Repository\DemandeRepository;
use App\Repository\OffreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/demande')]
class DemandeController extends AbstractController
{
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
        int $offre_id
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
            if (!$demande->getOffre()) {
                $this->addFlash('error', 'Aucune offre associée');
                return $this->redirectToRoute('app_front_active');
            }

            $file = $form->get('fichierPieceJointe')->getData();
            if ($file) {
                // Utiliser le nom original du fichier avec timestamp pour éviter les écrasements
                $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
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
                    return $this->redirectToRoute('app_demande_new', ['offre_id' => $offre_id]);
                }
            }

            $demandeRepository->save($demande, true);
            return $this->redirectToRoute('app_front');
        }

        return $this->render('demande/new.html.twig', [
            'form' => $form->createView(),
            'offre' => $offre
        ]);
    }

    #[Route('/{id}', name: 'app_demande_show', methods: ['GET'])]
    public function show(Demande $demande): Response
    {
        return $this->render('demande/show.html.twig', [
            'demande' => $demande,
        ]);
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
                // Utiliser le nom original avec timestamp
                $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $fileName = $originalFileName . '_' . time() . '.' . $extension;

                try {
                    // Supprimer l'ancien fichier
                    $oldFile = $demande->getFichierPieceJointe();
                    if ($oldFile && file_exists($this->getParameter('uploads_directory') . '/' . $oldFile)) {
                        unlink($this->getParameter('uploads_directory') . '/' . $oldFile);
                    }

                    // Déplacer le nouveau fichier
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
            // Supprimer le fichier associé
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