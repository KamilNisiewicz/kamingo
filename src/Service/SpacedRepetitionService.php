<?php

namespace App\Service;

use App\Entity\ProgressStatus;
use App\Entity\User;
use App\Entity\UserProgress;
use App\Entity\Word;
use Doctrine\ORM\EntityManagerInterface;

class SpacedRepetitionService
{
    // Interwały w dniach
    private const INTERVALS = [1, 3, 7, 14, 30];

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Tworzy nowy UserProgress dla nowego słówka
     */
    public function createProgress(User $user, Word $word): UserProgress
    {
        $progress = new UserProgress();
        $progress->setUser($user);
        $progress->setWord($word);
        $progress->setStatus(ProgressStatus::NEW);
        $progress->setRepetitions(0);
        $progress->setNextReviewDate(new \DateTimeImmutable('+1 day'));

        $this->entityManager->persist($progress);

        return $progress;
    }

    /**
     * Aktualizuje postęp użytkownika po przeglądzie karty
     *
     * @param UserProgress $progress
     * @param string $quality "again" | "good" | "easy"
     */
    public function updateProgress(UserProgress $progress, string $quality): void
    {
        $currentRepetitions = $progress->getRepetitions();
        $now = new \DateTimeImmutable();

        switch ($quality) {
            case 'again':
                // Cofamy o krok (minimum 1 dzień)
                $newRepetitions = max(0, $currentRepetitions - 1);
                $interval = self::INTERVALS[$newRepetitions] ?? 1;
                $progress->setStatus(ProgressStatus::LEARNING);
                break;

            case 'good':
                // Następny interwał
                $newRepetitions = $currentRepetitions + 1;
                $interval = self::INTERVALS[$newRepetitions] ?? 30;

                // Jeśli osiągnęliśmy interwał >= 14 dni, status = mastered
                if ($interval >= 14) {
                    $progress->setStatus(ProgressStatus::MASTERED);
                } else {
                    $progress->setStatus(ProgressStatus::LEARNING);
                }
                break;

            case 'easy':
                // Przeskakujemy interwał
                $newRepetitions = $currentRepetitions + 2;
                $interval = self::INTERVALS[$newRepetitions] ?? 30;

                if ($interval >= 14) {
                    $progress->setStatus(ProgressStatus::MASTERED);
                } else {
                    $progress->setStatus(ProgressStatus::LEARNING);
                }
                break;

            default:
                throw new \InvalidArgumentException("Invalid quality: {$quality}. Must be 'again', 'good', or 'easy'.");
        }

        $progress->setRepetitions($newRepetitions);
        $progress->setNextReviewDate($now->modify("+{$interval} days"));
        $progress->setLastReviewedAt($now);

        $this->entityManager->flush();
    }

    /**
     * Pobiera UserProgress dla użytkownika i słówka (lub tworzy nowy)
     */
    public function getOrCreateProgress(User $user, Word $word): UserProgress
    {
        $repository = $this->entityManager->getRepository(UserProgress::class);
        $progress = $repository->findOneBy([
            'user' => $user,
            'word' => $word,
        ]);

        if (!$progress) {
            $progress = $this->createProgress($user, $word);
            $this->entityManager->flush();
        }

        return $progress;
    }
}
