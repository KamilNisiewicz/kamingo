<?php

namespace App\Controller;

use App\Entity\WordCategory;
use App\Repository\SessionCompletionRepository;
use App\Repository\UserProgressRepository;
use App\Service\WordSelectionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private UserProgressRepository $userProgressRepository,
        private WordSelectionService $wordSelectionService,
        private SessionCompletionRepository $sessionCompletionRepository,
    ) {
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        $user = $this->getUser();
        $today = new \DateTimeImmutable('today');

        // Pobierz ukończone sesje z BAZY DANYCH
        $completedSessionsToday = $this->sessionCompletionRepository->findCompletedSessionsForDate($user, $today);
        $completedSessions = count($completedSessionsToday);

        // Policz dostępne słówka dla każdej kategorii
        $programmingNew = count($this->wordSelectionService->selectNewWords($user, WordCategory::PROGRAMMING, 5));
        $programmingReview = count($this->wordSelectionService->selectWordsForReview($user, WordCategory::PROGRAMMING, $today, 15));

        $travelNew = count($this->wordSelectionService->selectNewWords($user, WordCategory::TRAVEL, 5));
        $travelReview = count($this->wordSelectionService->selectWordsForReview($user, WordCategory::TRAVEL, $today, 15));

        // Completed categories z bazy danych
        $completedCategories = array_map(fn($sc) => $sc->getCategory(), $completedSessionsToday);

        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'completed_sessions' => $completedSessions,
            'total_sessions' => 2,
            'programming_new' => $programmingNew,
            'programming_review' => $programmingReview,
            'travel_new' => $travelNew,
            'travel_review' => $travelReview,
            'completed_categories' => $completedCategories,
            'current_date' => $today,
        ]);
    }
}
