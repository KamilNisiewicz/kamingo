<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Word;
use App\Entity\WordCategory;
use App\Repository\WordRepository;
use App\Repository\UserProgressRepository;

class WordSelectionService
{
    public function __construct(
        private WordRepository $wordRepository,
        private UserProgressRepository $userProgressRepository,
    ) {
    }

    /**
     * Wybiera nowe słówka dla użytkownika z danej kategorii
     * (słówka, których użytkownik jeszcze nie widział)
     */
    public function selectNewWords(User $user, WordCategory $category, int $limit = 5): array
    {
        // Pobierz ID słówek, które użytkownik już widział
        $seenWordIds = $this->userProgressRepository->createQueryBuilder('up')
            ->select('IDENTITY(up.word)')
            ->where('up.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleColumnResult();

        // Wybierz słówka z kategorii, których użytkownik NIE widział
        $qb = $this->wordRepository->createQueryBuilder('w')
            ->where('w.category = :category')
            ->setParameter('category', $category)
            ->setMaxResults($limit)
            ->orderBy('w.id', 'ASC');

        if (!empty($seenWordIds)) {
            $qb->andWhere('w.id NOT IN (:seenIds)')
                ->setParameter('seenIds', $seenWordIds);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Wybiera słówka do powtórki dla użytkownika z danej kategorii
     * (słówka, gdzie nextReviewDate <= dzisiaj)
     */
    public function selectWordsForReview(User $user, WordCategory $category, \DateTimeImmutable $date, int $limit = 15): array
    {
        $userProgressList = $this->userProgressRepository->createQueryBuilder('up')
            ->join('up.word', 'w')
            ->where('up.user = :user')
            ->andWhere('w.category = :category')
            ->andWhere('up.nextReviewDate <= :date')
            ->setParameter('user', $user)
            ->setParameter('category', $category)
            ->setParameter('date', $date)
            ->setMaxResults($limit)
            ->orderBy('up.nextReviewDate', 'ASC')
            ->getQuery()
            ->getResult();

        // Zwróć same obiekty Word
        return array_map(fn($progress) => $progress->getWord(), $userProgressList);
    }
}
