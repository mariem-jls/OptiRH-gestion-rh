<?php

namespace App\Controller\Admin\Evenement;

use App\Entity\Evenement\Evenement;
use App\Form\Evenement\EvenementType;
use App\Repository\Evenement\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Evenement\ReservationEvenementRepository;


#[Route('/evenement')]
class EvenementController extends AbstractController
{
    /*******************Liste des evenment back********* */
    #[Route('/', name: 'app_evenement_index', methods: ['GET'])]
    public function index(EvenementRepository $evenementRepository): Response
    {
         // Récupérer tous les événements
    $evenements = $evenementRepository->findAll();
    
    // Mettre à jour le statut pour chaque événement
    foreach ($evenements as $evenement) {
        $evenement->updateStatus();
    }
    return $this->render('evenement/index.html.twig', [
        'evenements' => $evenements,
    ]);
    }

  
    /***************Ajouter evenment*********** */
    #[Route('/new', name: 'app_evenement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EvenementRepository $evenementRepository): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            $evenement->updateStatus();
        
            if ($imageFile) {
                $originalFilename = $imageFile->getClientOriginalName();
                $newFilename = pathinfo($originalFilename, PATHINFO_FILENAME) . '-' . uniqid() . '.' . $imageFile->guessExtension();
        
                try {
                    // Déplacer le fichier dans le répertoire des uploads
                    $imageFile->move(
                        $this->getParameter('evenements_directory'), 
                        $newFilename
                    );
        
                    // Stocker le chemin de l'image dans l'entité Evenement
                    $evenement->setImage('uploads/evenements/' . $newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'L\'upload de l\'image a échoué.');
                    return $this->redirectToRoute('app_evenement_new');
                }
            }
        
            // Sauvegarder l'événement dans la base de données
            $evenementRepository->save($evenement, true);
        
            return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
        
        }

        return $this->renderForm('evenement/new.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
        ]);
    }


    /***********Modifier evenment************* */

    #[Route('/{id}/edit', name: 'app_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evenement $evenement, EvenementRepository $evenementRepository): Response
    {
        $ancienneImage = $evenement->getImage();
    
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            $evenement->updateStatus();
    
            if ($imageFile) {
                // Supprimer l'ancienne image si elle existe
                $cheminComplet = $this->getParameter('kernel.project_dir') . '/public/' . $ancienneImage;
                if ($ancienneImage && file_exists($cheminComplet)) {
                    unlink($cheminComplet);
                }
    
                // Générer un nom unique et déplacer le fichier
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
    
                try {
                    $imageFile->move(
                        $this->getParameter('evenements_directory'),
                        $newFilename
                    );
    
                    $evenement->setImage('uploads/evenements/' . $newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Échec de l\'upload de l\'image.');
                    return $this->redirectToRoute('app_evenement_edit', ['id' => $evenement->getId()]);
                }
            } else {
                // Aucun nouveau fichier => conserver l'ancienne image
                $evenement->setImage($ancienneImage);
            }
    
            $evenementRepository->save($evenement, true);
    
            return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->renderForm('evenement/edit.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
            'current_image' => $ancienneImage,
        ]);
    }
    

    /************Supprimer evenement************** */

    #[Route('/{id}', name: 'app_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement, EvenementRepository $evenementRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$evenement->getId(), $request->request->get('_token'))) {
            $evenementRepository->remove($evenement, true);
        }

        return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
    }

    /*****************Afficher les reservation du un evenement******* */
    #[Route('/evenement/{id}', name: 'app_evenement_show', methods: ['GET'])]
    public function show(Evenement $evenement,ReservationEvenementRepository $reservationRepo): Response {
        $reservations = $reservationRepo->findByEvenementId($evenement->getId());

        return $this->render('evenement/show.html.twig', [
            'evenement' => $evenement,
            'reservations' => $reservations,
        ]);
    }

    /****************Afficher les evenment front********* */

    #[Route('/evenements/indexfront', name: 'app_evenement_indexfront', methods: ['GET'])]
    public function showall(EvenementRepository $evenementRepository): Response
    {
        $user = $this->getUser();
        
        return $this->render('evenement/indexfront.html.twig', [
            'evenements' => $evenementRepository->findAll(),
        ]);
    
    }
/*************detaille d un evenmentfront*********** */
    #[Route('/event/{id}', name: 'event_details', methods: ['GET'])]
    public function eventDetails($id, EvenementRepository $EvenementRepository): Response
    {
        // Utilisez find() pour rechercher un événement par son identifiant
        $evenement = $EvenementRepository->find($id);
        
        if (!$evenement) {
            throw $this->createNotFoundException('L\'événement n\'a pas été trouvé');
        }

        // Rendu du template avec l'événement
        return $this->render('reservation_evenement/show.html.twig', [
            'evenement' => $evenement,
        ]);
    }






}
