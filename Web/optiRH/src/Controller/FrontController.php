<?php

namespace App\Controller;

use App\Entity\Offre;
use App\Repository\OffreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/front')]
class FrontController extends AbstractController
{
    #[Route('/front', name: 'app_front')]
    public function index(OffreRepository $offreRepository): Response
    {
        // 1. Mettre à jour les offres expirées
        $offreRepository->updateExpiredOffresToBrouillon();
        // 2. Afficher les offres actives non expirées
        $offres = $offreRepository->findActiveOffres();

        return $this->render('front-home/index.html.twig', [
            'controller_name' => 'FrontController',
            'offres' => $offres, // Passer les offres ici

        ]);
    }

    #[Route('/active', name: 'app_front_active')]
    public function offresActives(OffreRepository $offreRepository): Response
    {
        // 1. Mettre à jour les offres expirées
        $offreRepository->updateExpiredOffresToBrouillon();
        // 2. Afficher les offres actives non expirées
        $offres = $offreRepository->findActiveOffres();
        $totalOffres = count($offres);

        return $this->render('front-home/offresActives.html.twig', [
            'offres' => $offres,
            'totalOffres' => $totalOffres
        ]);
    }

    #[Route('/offre/{id}', name: 'app_front_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Offre $offre): Response
    {
        return $this->render('front-home/show.html.twig', [
            'offre' => $offre,
        ]);
    }

    #[Route('/about', name: 'app_front_about')]
    public function about(): Response
    {
        return $this->render('front-home/about.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
}
