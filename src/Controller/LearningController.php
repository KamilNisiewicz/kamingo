<?php

namespace App\Controller;

use App\Entity\SessionCompletion;
use App\Entity\WordCategory;
use App\Repository\SessionCompletionRepository;
use App\Repository\WordRepository;
use App\Service\SpacedRepetitionService;
use App\Service\WordSelectionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class LearningController extends AbstractController
{
    public function __construct(
        private WordSelectionService $wordSelectionService,
        private SpacedRepetitionService $spacedRepetitionService,
        private WordRepository $wordRepository,
        private SessionCompletionRepository $sessionCompletionRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/learn/programming', name: 'app_learn_programming')]
    public function learnProgramming(SessionInterface $session): Response
    {
        return $this->startSession(WordCategory::PROGRAMMING, $session);
    }

    #[Route('/learn/travel', name: 'app_learn_travel')]
    public function learnTravel(SessionInterface $session): Response
    {
        return $this->startSession(WordCategory::TRAVEL, $session);
    }

    private function startSession(WordCategory $category, SessionInterface $session): Response
    {
        $user = $this->getUser();
        $today = new \DateTimeImmutable();

        // Wybierz 5 nowych słówek
        $newWords = $this->wordSelectionService->selectNewWords($user, $category, 5);

        // Wybierz słówka do review
        $reviewWords = $this->wordSelectionService->selectWordsForReview($user, $category, $today, 15);

        // Połącz w jedną sesję
        $allWords = array_merge($newWords, $reviewWords);

        if (empty($allWords)) {
            $this->addFlash('info', 'Brak słówek do nauki w tej kategorii.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Zapisz sesję
        $sessionData = [
            'category' => $category->value,
            'words' => array_map(fn($word) => $word->getId(), $allWords),
            'current_index' => 0,
            'show_answer' => false,
        ];

        $session->set('learning_session', $sessionData);

        return $this->redirectToRoute('app_learn_card');
    }

    #[Route('/learn/card', name: 'app_learn_card')]
    public function showCard(SessionInterface $session): Response
    {
        $sessionData = $session->get('learning_session');

        if (!$sessionData) {
            $this->addFlash('error', 'Brak aktywnej sesji nauki.');
            return $this->redirectToRoute('app_dashboard');
        }

        $currentIndex = $sessionData['current_index'];
        $wordIds = $sessionData['words'];
        $showAnswer = $sessionData['show_answer'];

        if ($currentIndex >= count($wordIds)) {
            // Sesja zakończona - zapisz statystyki
            $completedData = [
                'category' => $sessionData['category'],
                'total_cards' => count($wordIds),
            ];
            $session->set('session_completed', $completedData);
            $session->remove('learning_session');
            return $this->redirectToRoute('app_learn_completed');
        }

        $wordId = $wordIds[$currentIndex];
        $word = $this->wordRepository->find($wordId);

        if (!$word) {
            $this->addFlash('error', 'Nie znaleziono słówka.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Pobierz lub utwórz UserProgress
        $progress = $this->spacedRepetitionService->getOrCreateProgress($this->getUser(), $word);

        return $this->render('learning/card.html.twig', [
            'word' => $word,
            'progress' => $progress,
            'show_answer' => $showAnswer,
            'current_index' => $currentIndex + 1,
            'total_cards' => count($wordIds),
            'category' => $sessionData['category'],
        ]);
    }

    #[Route('/learn/show-answer', name: 'app_learn_show_answer', methods: ['POST'])]
    public function showAnswer(SessionInterface $session): Response
    {
        $sessionData = $session->get('learning_session');

        if (!$sessionData) {
            return $this->redirectToRoute('app_dashboard');
        }

        $sessionData['show_answer'] = true;
        $session->set('learning_session', $sessionData);

        return $this->redirectToRoute('app_learn_card');
    }

    #[Route('/learn/review/{quality}', name: 'app_learn_review', methods: ['POST'])]
    public function reviewCard(string $quality, SessionInterface $session, Request $request): Response
    {
        $sessionData = $session->get('learning_session');

        if (!$sessionData || !in_array($quality, ['again', 'good', 'easy'])) {
            return $this->redirectToRoute('app_dashboard');
        }

        $currentIndex = $sessionData['current_index'];
        $wordIds = $sessionData['words'];
        $wordId = $wordIds[$currentIndex];

        $word = $this->wordRepository->find($wordId);
        $progress = $this->spacedRepetitionService->getOrCreateProgress($this->getUser(), $word);

        // Aktualizuj postęp
        $this->spacedRepetitionService->updateProgress($progress, $quality);

        // Następna karta
        $sessionData['current_index']++;
        $sessionData['show_answer'] = false;
        $session->set('learning_session', $sessionData);

        return $this->redirectToRoute('app_learn_card');
    }

    #[Route('/learn/completed', name: 'app_learn_completed')]
    public function completed(SessionInterface $session): Response
    {
        $completedData = $session->get('session_completed');

        if (!$completedData) {
            return $this->redirectToRoute('app_dashboard');
        }

        $user = $this->getUser();
        $today = new \DateTimeImmutable('today');

        // Zapisz informację że ta kategoria została ukończona dzisiaj (do bazy!)
        $sessionCompletion = new SessionCompletion();
        $sessionCompletion->setUser($user);
        $sessionCompletion->setCategory($completedData['category']);
        $sessionCompletion->setCompletedDate($today);

        try {
            $this->entityManager->persist($sessionCompletion);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // Jeśli już istnieje (unique constraint), ignoruj błąd
            // Oznacza to że sesja już była completed dzisiaj
        }

        // Usuń dane z sesji po wyświetleniu
        $session->remove('session_completed');

        // Sprawdź która druga sesja nie jest ukończona
        $otherCategory = $completedData['category'] === 'programming' ? 'travel' : 'programming';
        $otherCategoryCompleted = $this->sessionCompletionRepository->isSessionCompletedToday($user, $otherCategory, $today);

        return $this->render('learning/completed.html.twig', [
            'category' => $completedData['category'],
            'total_cards' => $completedData['total_cards'],
            'other_category_completed' => $otherCategoryCompleted,
        ]);
    }
}
