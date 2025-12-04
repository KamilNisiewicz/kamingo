<?php

namespace App\Repository;

use App\Entity\SessionCompletion;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SessionCompletionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SessionCompletion::class);
    }

    /**
     * Znajdź ukończone sesje dla użytkownika w danym dniu
     */
    public function findCompletedSessionsForDate(User $user, \DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('sc')
            ->andWhere('sc.user = :user')
            ->andWhere('sc.completedDate = :date')
            ->setParameter('user', $user)
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    /**
     * Sprawdź czy dana sesja została ukończona dzisiaj
     */
    public function isSessionCompletedToday(User $user, string $category, \DateTimeInterface $date): bool
    {
        $result = $this->createQueryBuilder('sc')
            ->select('COUNT(sc.id)')
            ->andWhere('sc.user = :user')
            ->andWhere('sc.category = :category')
            ->andWhere('sc.completedDate = :date')
            ->setParameter('user', $user)
            ->setParameter('category', $category)
            ->setParameter('date', $date)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }
}
