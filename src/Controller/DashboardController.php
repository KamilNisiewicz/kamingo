<?php

namespace App\Controller;

use App\Repository\UserProgressRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private UserProgressRepository $userProgressRepository,
    ) {
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        $user = $this->getUser();

        // Sprawdź ile sesji ukończono dzisiaj
        $today = new \DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');

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

        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'completed_sessions' => $completedSessions,
            'total_sessions' => 2,
        ]);
    }
}
