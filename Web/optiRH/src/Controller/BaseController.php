<?php

namespace App\Controller;

use App\Repository\OffreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class BaseController extends AbstractController
{

    public function __construct(Environment $twig)
    {
        $this->loader = $twig->getLoader();
    }

    #[Route('/', name: 'base')]
    public function index(OffreRepository $offreRepository): Response
    {
        // Récupérer les offres actives
        $offres = $offreRepository->findActiveOffres();

        return $this->render('front-home/index.html.twig', [
            'controller_name' => 'FrontController',
            'offres' => $offres, // Passer les offres ici

        ]);
    }
}
