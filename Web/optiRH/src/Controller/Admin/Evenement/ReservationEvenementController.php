<?php

namespace App\Controller\Admin\Evenement;

use App\Entity\Evenement\ReservationEvenement;
use App\Form\Evenement\ReservationEvenementType;
use App\Repository\Evenement\ReservationEvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Evenement\Evenement;
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
        $user = $this->getUser();
        $reservation->setFirstName($user->getNom()); 
        $reservation->setEmail($user->getEmail()); 
        if ($user) {
            $reservation->setUser($user); 
        } else {
            
            return $this->redirectToRoute('app_login');
        }
        $existingReservation = $entityManager->getRepository(ReservationEvenement::class)
        ->findOneBy(['user' => $user, 'Evenement' => $evenement]);

        if ($existingReservation) {
            $this->addFlash('deja_reserve', 'Vous avez déjà réservé pour cet événement.');
            return $this->redirectToRoute('app_evenement_indexfront');
            
        }
        
        
        if ($evenement->getNbrPersonnes() == 0) {
            $this->addFlash('complet', 'Désolé, cet événement est complet.');
            return $this->redirectToRoute('app_evenement_indexfront');
        }
            // Vérifier si la date de début est déjà passée
        $now = new \DateTime();
        if ($evenement->getDateDebut() <= $now) {
            $this->addFlash('date_passee', 'Désolé, l\'événement a déjà commencé.');
            return $this->redirectToRoute('app_evenement_indexfront');
        }


        $form = $this->createForm(ReservationEvenementType::class, $reservation);
        
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $evenement->decrementNbrPersonnes();
            $entityManager->persist($reservation); 
            $entityManager->flush(); 
    
            return $this->redirectToRoute('app_evenement_indexfront', [], Response::HTTP_SEE_OTHER);
        }
    
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
            try{
                $reservationEvenementRepository->save($reservationEvenement, true);
                $this->addFlash('modifiee', 'La réservation a été modifiée avec succès !');
                return $this->redirectToRoute('app_user_reservations', [], Response::HTTP_SEE_OTHER);

            }catch(\Exception $e){
                $this->addFlash('erreur', 'Une erreur est survenue lors de la modification de la réservation.');

            }
            

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
                $evenement = $reservationEvenement->getEvenement();
                $evenement->incrementNbrPersonnes(); // Appel de la méthode pour augmenter le nombre de places disponibles

                $reservationEvenementRepository->remove($reservationEvenement, true);
                $this->addFlash('réservation', 'La réservation a été supprimée avec succès.');
            } else {
                $this->addFlash('Impossibleréservation', 'Impossible de supprimer la réservation : l\'événement commence dans moins de 24 heures.');
            }
        } else {
            $this->addFlash('Impossibleréservation', 'Token CSRF invalide.');
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
     
         
     
         return $this->render('reservation_evenement/Mes_Reservations.html.twig', [
             'reservations' => $reservations,
             
         ]);
     }
     
     
}
