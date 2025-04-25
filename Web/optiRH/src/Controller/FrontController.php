<?php

namespace App\Controller;

use App\Entity\Offre;
use App\Repository\OffreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/front')]
class FrontController extends AbstractController
{
    #[Route('/front', name: 'app_front')]
    public function index(OffreRepository $offreRepository): Response
    {
        $offreRepository->updateExpiredOffresToBrouillon();
        $offres = $offreRepository->findActiveOffres();

        return $this->render('front-home/index.html.twig', [
            'controller_name' => 'FrontController',
            'offres' => $offres,
        ]);
    }

    #[Route('/active', name: 'app_front_active')]
    public function offresActives(OffreRepository $offreRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $offreRepository->updateExpiredOffresToBrouillon();

        // Récupérer les paramètres initiaux
        $keyword = $request->query->get('keyword', '');
        $modeTravail = $request->query->has('modeTravail') ? (array) $request->query->get('modeTravail') : [];
        $typeContrat = $request->query->get('typeContrat', '');
        $experience = $request->query->has('experience') ? (array) $request->query->get('experience') : [];
        $sortBy = $request->query->get('sortBy', 'none');
        $page = $request->query->getInt('page', 1);

        // Récupérer la requête paginable
        $queryBuilder = $offreRepository->findByFilters($keyword, $modeTravail, $typeContrat, $experience, $sortBy);

        // Paginer les résultats
        $pagination = $paginator->paginate(
            $queryBuilder,
            $page,
            10 // Nombre d'éléments par page
        );

        return $this->render('front-home/offresActives.html.twig', [
            'totalOffres' => $pagination->getTotalItemCount(),
            'pagination' => $pagination,
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

    #[Route('/filter', name: 'app_front_filter', methods: ['POST'])]
    public function filter(Request $request, OffreRepository $offreRepository, Environment $twig, PaginatorInterface $paginator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $keyword = $data['keyword'] ?? '';
        $modeTravail = $data['modeTravail'] ?? [];
        $typeContrat = $data['typeContrat'] ?? '';
        $experience = $data['experience'] ?? [];
        $sortBy = $data['sortBy'] ?? 'none';
        $page = $data['page'] ?? 1; // Récupérer le numéro de page (par défaut : 1)

        // Récupérer la requête paginable
        $queryBuilder = $offreRepository->findByFilters($keyword, $modeTravail, $typeContrat, $experience, $sortBy);

        // Paginer les résultats
        $pagination = $paginator->paginate(
            $queryBuilder, // Requête à paginer
            $page,         // Numéro de page
            10             // Nombre d'éléments par page (ajustez selon vos besoins)
        );

        // Rendre la liste des offres
        $html = $twig->render('front-home/_offres_list.html.twig', [
            'offres' => $pagination,
        ]);

        // Rendre la pagination
        $paginationHtml = $twig->render('front-home/_pagination.html.twig', [
            'pagination' => $pagination,
        ]);

        return new JsonResponse([
            'html' => $html,
            'pagination' => $paginationHtml,
            'total' => $pagination->getTotalItemCount(),
        ]);
    }}