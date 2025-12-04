<?php

namespace App\Controller;

use App\Entity\WordCategory;
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
    ) {
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        $user = $this->getUser();
        $today = new \DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');

        // Sprawdź ile sesji ukończono dzisiaj
        $completedSessions = $this->userProgressRepository->createQueryBuilder('up')
            ->select('COUNT(DISTINCT w.category) as sessions_count')
            ->join('up.word', 'w')
            ->where('up.user = :user')
            ->andWhere('up.lastReviewedAt >= :today')
            ->andWhere('up.lastReviewedAt < :tomorrow')
            ->setParameter('user', $user)
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();

        // Policz dostępne słówka dla każdej kategorii
        $programmingNew = count($this->wordSelectionService->selectNewWords($user, WordCategory::PROGRAMMING, 5));
        $programmingReview = count($this->wordSelectionService->selectWordsForReview($user, WordCategory::PROGRAMMING, $today, 15));

        $travelNew = count($this->wordSelectionService->selectNewWords($user, WordCategory::TRAVEL, 5));
        $travelReview = count($this->wordSelectionService->selectWordsForReview($user, WordCategory::TRAVEL, $today, 15));

        // Sprawdź które sesje zostały ukończone dzisiaj
        $completedCategories = $this->userProgressRepository->createQueryBuilder('up')
            ->select('DISTINCT w.category')
            ->join('up.word', 'w')
            ->where('up.user = :user')
            ->andWhere('up.lastReviewedAt >= :today')
            ->andWhere('up.lastReviewedAt < :tomorrow')
            ->setParameter('user', $user)
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleColumnResult();

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
