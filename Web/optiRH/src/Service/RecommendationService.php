<?php

namespace App\Service;

use App\Repository\Evenement\ReservationEvenementRepository;
use App\Repository\Evenement\FavorisEvenementRepository;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use App\Entity\User;

class RecommendationService
{
    private PaginatedFinderInterface $evenementFinder;
    private ReservationEvenementRepository $reservationRepository;
    private FavorisEvenementRepository $favoriRepository;

    public function __construct(
        PaginatedFinderInterface $evenementFinder,
        ReservationEvenementRepository $reservationRepository,
        FavorisEvenementRepository $favoriRepository
    ) {
        $this->evenementFinder = $evenementFinder;
        $this->reservationRepository = $reservationRepository;
        $this->favoriRepository = $favoriRepository;
    }

    public function getRecommendedEvents(User $user): array
    {
        $reservations = $this->reservationRepository->findBy(['user' => $user]);
        $favoris = $this->favoriRepository->findBy(['id_user' => $user]);

        // Compter occurrences des types et modalités
        $typeCounts = [];
        $modaliteCounts = [];
        $prices = [];

        foreach ($reservations as $r) {
            $event = $r->getEvenement();
            if ($event) {
                $type = $event->getType();
                $modalite = $event->getModalite();
                $prix = $event->getPrix();

                $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
                $modaliteCounts[$modalite] = ($modaliteCounts[$modalite] ?? 0) + 1;

                if ($prix !== null) {
                    $prices[] = $prix;
                }
            }
        }

        foreach ($favoris as $f) {
            $event = $f->getIdEvenement();
            if ($event) {
                $type = $event->getType();
                $modalite = $event->getModalite();
                $prix = $event->getPrix();

                $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
                $modaliteCounts[$modalite] = ($modaliteCounts[$modalite] ?? 0) + 1;

                if ($prix !== null) {
                    $prices[] = $prix;
                }
            }
        }

        // Déterminer le type et la modalité préférés
        $preferredType = !empty($typeCounts) ? array_keys($typeCounts, max($typeCounts))[0] : null;
        $preferredModalite = !empty($modaliteCounts) ? array_keys($modaliteCounts, max($modaliteCounts))[0] : null;

        // Calculer le prix moyen
        $userAvgPrice = !empty($prices) ? array_sum($prices) / count($prices) : 0;

        // Récupérer tous les événements
        $events = $this->evenementFinder->find([]);

        $scoredEvents = [];

        foreach ($events as $event) {
            $score = 0;

            // Compter les réservations et favoris pour cet événement
            $eventReservations = $this->reservationRepository->findBy(['Evenement' => $event]);
            $score += count($eventReservations) * 6;

            $eventFavoris = $this->favoriRepository->findBy(['id_evenement' => $event]);
            $score += count($eventFavoris) * 4;

            // Vérifier si c'est la modalité préférée
            if ($preferredModalite && $event->getModalite() === $preferredModalite) {
                $score += 2;
            }

            // Vérifier si c'est le type préféré
            if ($preferredType && $event->getType() === $preferredType) {
                $score += 3;
            }

            // Vérifier si prix est proche du prix moyen
            if ($userAvgPrice > 0 && abs($event->getPrix() - $userAvgPrice) <= $userAvgPrice * 0.2) {
                $score += 1;
            }

            $scoredEvents[] = ['event' => $event, 'score' => $score];
        }

        // Trier par score décroissant
        usort($scoredEvents, fn($a, $b) => $b['score'] <=> $a['score']);

        return $scoredEvents;
    }
}
