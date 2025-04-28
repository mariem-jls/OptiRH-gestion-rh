<?php
// src/Controller/InterviewController.php
namespace App\Controller;

use App\Entity\Demande;
use App\Entity\Interview;
use App\Form\InterviewType;
use App\Repository\DemandeRepository;
use App\Repository\InterviewRepository;
use App\Service\SlotSuggester;
use App\Service\EmailService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InterviewController extends AbstractController
{
    #[Route('/plan-interview/{demandeId}', name: 'plan_interview', methods: ['GET', 'POST'])]
    public function plan(
        int $demandeId,
        Request $request,
        DemandeRepository $demandeRepository,
        InterviewRepository $interviewRepository,
        SlotSuggester $slotSuggester
    ): Response {
        $demande = $demandeRepository->find($demandeId);
        if (!$demande) {
            throw $this->createNotFoundException('Demande non trouvée');
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Seuls les recruteurs peuvent planifier des entretiens.');

        $interview = new Interview();
        $interview->setDemande($demande);
        $form = $this->createForm(InterviewType::class, $interview);

        $suggestedSlots = $slotSuggester->suggestSlots($demande);

        return $this->render('interview/plan.html.twig', [
            'form' => $form->createView(),
            'demande' => $demande,
            'suggested_slots' => $suggestedSlots,
        ]);
    }

    #[Route('/api/interview/suggest-slots/{demandeId}', name: 'api_suggest_slots', methods: ['GET'])]
    public function suggestSlots(
        int $demandeId,
        DemandeRepository $demandeRepository,
        SlotSuggester $slotSuggester
    ): JsonResponse {
        $demande = $demandeRepository->find($demandeId);
        if (!$demande) {
            return new JsonResponse(['error' => 'Demande non trouvée'], 404);
        }

        $slots = $slotSuggester->suggestSlots($demande);
        $formattedSlots = array_map(function ($slot) {
            return [
                'dateTime' => $slot->dateTime->format('c'),
                'period' => $slot->period,
            ];
        }, $slots);

        return new JsonResponse(['slots' => $formattedSlots]);
    }

    #[Route('/api/interview/select-slot/{demandeId}', name: 'api_select_slot', methods: ['POST'])]
    public function selectSlot(
        int $demandeId,
        Request $request,
        DemandeRepository $demandeRepository,
        InterviewRepository $interviewRepository,
        EmailService $emailService,
        LoggerInterface $logger
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Seuls les recruteurs peuvent planifier des entretiens.');

        $demande = $demandeRepository->find($demandeId);
        if (!$demande) {
            return new JsonResponse(['error' => 'Demande non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['dateTime']) || !isset($data['googleMeetLink'])) {
            return new JsonResponse(['error' => 'Données incomplètes'], 400);
        }

        try {
            $dateTime = new \DateTime($data['dateTime']);

            // Vérifier les conflits
            if ($interviewRepository->isSlotTaken($dateTime)) {
                $logger->warning('Conflit d\'horaire détecté pour l\'entretien à ' . $dateTime->format('Y-m-d H:i'));
                return new JsonResponse(['error' => 'Ce créneau horaire est déjà pris. Veuillez choisir un autre horaire.'], 409);
            }

            $interview = new Interview();
            $interview->setDemande($demande)
                ->setDateTime($dateTime)
                ->setGoogleMeetLink($data['googleMeetLink']);

            $interviewRepository->save($interview, true);

            $emailService->sendInterviewConfirmationEmail(
                $demande->getEmail(),
                $demande->getNomComplet(),
                $demande->getOffre() ? $demande->getOffre()->getPoste() : 'Poste non spécifié',
                $interview->getDateTime(),
                $interview->getGoogleMeetLink()
            );

            $logger->info('Entretien planifié via API pour la demande ID: ' . $demandeId);
            return new JsonResponse(['status' => 'success']);
        } catch (\Exception $e) {
            $logger->error('Erreur lors de la planification via API: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Erreur lors de la planification: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/interview/cancel/{interviewId}', name: 'api_cancel_interview', methods: ['DELETE'])]
    public function cancelInterview(
        int $interviewId,
        InterviewRepository $interviewRepository,
        EmailService $emailService,
        LoggerInterface $logger
    ): JsonResponse {
        // Vérifier les autorisations
        try {
            $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Seuls les recruteurs peuvent annuler des entretiens.');
        } catch (\Exception $e) {
            $logger->error('Accès refusé pour l\'annulation de l\'entretien ID: ' . $interviewId . '. Erreur: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Accès non autorisé'], 403);
        }

        // Trouver l'entretien
        $interview = $interviewRepository->find($interviewId);
        if (!$interview) {
            $logger->error('Entretien non trouvé pour ID: ' . $interviewId);
            return new JsonResponse(['error' => 'Entretien non trouvé'], 404);
        }

        $demande = $interview->getDemande();
        if (!$demande) {
            $logger->error('Demande non trouvée pour l\'entretien ID: ' . $interviewId);
            return new JsonResponse(['error' => 'Demande associée non trouvée'], 500);
        }

        $logger->info('Annulation de l\'entretien ID: ' . $interviewId . ' pour la demande ID: ' . $demande->getId());

        // Supprimer l'entretien
        try {
            $interviewRepository->remove($interview, true);
            $logger->info('Entretien ID: ' . $interviewId . ' supprimé avec succès');
        } catch (\Exception $e) {
            $logger->error('Erreur lors de la suppression de l\'entretien ID: ' . $interviewId . '. Erreur: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Erreur lors de la suppression de l\'entretien'], 500);
        }

        // Envoyer l'email de notification
        try {
            $offre = $demande->getOffre();
            $poste = $offre ? $offre->getPoste() : 'Poste non spécifié';
            $emailService->sendInterviewCancellationEmail(
                $demande->getEmail(),
                $demande->getNomComplet(),
                $poste,
                $interview->getDateTime()
            );
            $logger->info('Email d\'annulation envoyé à: ' . $demande->getEmail());
        } catch (\Exception $e) {
            $logger->warning('Erreur lors de l\'envoi de l\'email d\'annulation à ' . $demande->getEmail() . ': ' . $e->getMessage());
            // Continuer même si l'email échoue
        }

        return new JsonResponse(['status' => 'success']);
    }
}