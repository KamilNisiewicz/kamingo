<?php

namespace App\Entity;

use App\Repository\UserProgressRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: UserProgressRepository::class)]
#[ORM\Table(name: 'user_progress')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_WORD', columns: ['user_id', 'word_id'])]
#[UniqueEntity(fields: ['user', 'word'], message: 'This user already has progress for this word')]
class UserProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Word::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Word $word = null;

    #[ORM\Column(type: 'string', enumType: ProgressStatus::class)]
    private ?ProgressStatus $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $nextReviewDate = null;

    #[ORM\Column]
    private ?int $repetitions = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastReviewedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = ProgressStatus::NEW;
        $this->repetitions = 0;
        $this->nextReviewDate = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getWord(): ?Word
    {
        return $this->word;
    }

    public function setWord(?Word $word): static
    {
        $this->word = $word;

        return $this;
    }

    public function getStatus(): ?ProgressStatus
    {
        return $this->status;
    }

    public function getStatusValue(): string
    {
        return $this->status?->value ?? 'N/A';
    }

    public function setStatus(ProgressStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getNextReviewDate(): ?\DateTimeImmutable
    {
        return $this->nextReviewDate;
    }

    public function setNextReviewDate(\DateTimeImmutable $nextReviewDate): static
    {
        $this->nextReviewDate = $nextReviewDate;

        return $this;
    }

    public function getRepetitions(): ?int
    {
        return $this->repetitions;
    }

    public function setRepetitions(int $repetitions): static
    {
        $this->repetitions = $repetitions;

        return $this;
    }

    public function getLastReviewedAt(): ?\DateTimeImmutable
    {
        return $this->lastReviewedAt;
    }

    public function setLastReviewedAt(?\DateTimeImmutable $lastReviewedAt): static
    {
        $this->lastReviewedAt = $lastReviewedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
