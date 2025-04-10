<?php

namespace App\Controller;

use App\Entity\Evenement\ReservationEvenement;
use App\Form\Evenement\ReservationEvenementType;
use App\Repository\Evenement\ReservationEvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Evenement\Evenement;
use App\Form\Users\Users;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface; 





#[Route('/reservation/evenement')]
class ReservationEvenementController extends AbstractController
{
    /*******Afficher toute les reservation******* */
    #[Route('/', name: 'app_reservation_evenement_index', methods: ['GET'])]
    public function index(ReservationEvenementRepository $reservationEvenementRepository): Response
    {
        return $this->render('reservation_evenement/index.html.twig', [
            'reservation_evenements' => $reservationEvenementRepository->findAll(),
        ]);
    }
    

    /**************resrever un evenement************ */
    #[Route('/new/{id}', name: 'app_reservation_evenement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        $reservation = new ReservationEvenement();
        $reservation->setEvenement($evenement); 
    
        // Récupérer l'utilisateur connecté
        $user = $this->getUser(); 
        if ($user) {
            $reservation->setUser($user); // Lier la réservation à l'utilisateur connecté
        } else {
            
            return $this->redirectToRoute('app_login');
        }
    
        $form = $this->createForm(ReservationEvenementType::class, $reservation);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation); 
            $entityManager->flush(); 
    
            return $this->redirectToRoute('app_evenement_indexfront', [], Response::HTTP_SEE_OTHER);
        }
    
        // Si le formulaire est soumis mais non valide, on affiche les erreurs dans le formulaire
        return $this->render('reservation_evenement/new.html.twig', [
            'form' => $form->createView(),
            'evenement' => $evenement,
        ]);
    }
    
    /**************Modifier une reservation************* */

    #[Route('/{id}/edit', name: 'app_reservation_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ReservationEvenement $reservationEvenement, ReservationEvenementRepository $reservationEvenementRepository): Response
    {
        $form = $this->createForm(ReservationEvenementType::class, $reservationEvenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservationEvenementRepository->save($reservationEvenement, true);

            return $this->redirectToRoute('app_user_reservations', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm( 'reservation_evenement/edit.html.twig', [
            'reservation_evenement' => $reservationEvenement,
            'form' => $form,
        ]);
    }

    /**********Supprimer une reservation********* */
    #[Route('/{id}', name: 'app_reservation_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, ReservationEvenement $reservationEvenement, ReservationEvenementRepository $reservationEvenementRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservationEvenement->getId(), $request->request->get('_token'))) {
            $dateEvenement = $reservationEvenement->getEvenement()->getDateDebut();
            $now = new \DateTime();
            
            // Calculer la différence entre maintenant et la date de l'événement
            $diff = $now->diff($dateEvenement);
            
            $hoursLeft = ($diff->days * 24) + $diff->h;
            
            if ($dateEvenement > $now && $hoursLeft > 24) {
                $reservationEvenementRepository->remove($reservationEvenement, true);
                $this->addFlash('success', 'La réservation a été supprimée avec succès.');
            } else {
                $this->addFlash('error', 'Impossible de supprimer la réservation : l\'événement commence dans moins de 24 heures.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_user_reservations');
    }




    /************Afficher les resrvations pour un user******** */
     #[Route('/reservations/user', name: 'app_user_reservations')]
     public function showUserReservations(ReservationEvenementRepository $reservationEvenementRepository): Response
     {
         $user = $this->getUser();
     
         if (!$user) {
             return $this->redirectToRoute('app_login');
         }
     
         $reservations = $reservationEvenementRepository->findBy(['user' => $user]);
     
         $formReservations = [];
     
         foreach ($reservations as $reservation) {
             $form = $this->createForm(ReservationEvenementType::class, $reservation);
             $formReservations[$reservation->getId()] = $form->createView();
         }
     
         return $this->render('reservation_evenement/Mes_Reservations.html.twig', [
             'reservations' => $reservations,
             'formReservations' => $formReservations,
         ]);
     }
     
     
}
