<?php


namespace App\Controller\Admin\Evenement;

use App\Entity\Evenement\FavorisEvenement;
use App\Form\Evenement\FavorisEvenementType;
use App\Repository\Evenement\FavorisEvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/evenement/favoris/evenement')]
final class FavorisEvenementController extends AbstractController
{
    #[Route(name: 'app_evenement_favoris_evenement_index', methods: ['GET'])]
    public function index(FavorisEvenementRepository $favorisEvenementRepository): Response
    {
        return $this->render('evenement/favoris_evenement/index.html.twig', [
            'favoris_evenements' => $favorisEvenementRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_evenement_favoris_evenement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $favorisEvenement = new FavorisEvenement();
        $form = $this->createForm(FavorisEvenementType::class, $favorisEvenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($favorisEvenement);
            $entityManager->flush();

            return $this->redirectToRoute('app_evenement_favoris_evenement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evenement/favoris_evenement/new.html.twig', [
            'favoris_evenement' => $favorisEvenement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_evenement_favoris_evenement_show', methods: ['GET'])]
    public function show(FavorisEvenement $favorisEvenement): Response
    {
        return $this->render('evenement/favoris_evenement/show.html.twig', [
            'favoris_evenement' => $favorisEvenement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_evenement_favoris_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FavorisEvenement $favorisEvenement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FavorisEvenementType::class, $favorisEvenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_evenement_favoris_evenement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evenement/favoris_evenement/edit.html.twig', [
            'favoris_evenement' => $favorisEvenement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_evenement_favoris_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, FavorisEvenement $favorisEvenement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$favorisEvenement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($favorisEvenement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_evenement_favoris_evenement_index', [], Response::HTTP_SEE_OTHER);
    }
}
