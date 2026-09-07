<?php

namespace App\Entity;

use App\Repository\AvisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AvisRepository::class)]
#[ORM\Table(name: 'avis')]
#[ORM\Index(name: 'idx_avis_reported_created', columns: ['signale', 'created_at'])]
#[ORM\Index(name: 'idx_avis_user_reported', columns: ['user_id', 'signale'])]
class Avis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'avis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Recette::class, inversedBy: 'avis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recette $recette = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $note = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $signale = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motifSignalement = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'reponses')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?self $parentAvis = null;

    #[ORM\OneToMany(mappedBy: 'parentAvis', targetEntity: self::class, cascade: ['persist', 'remove'])]
    private \Doctrine\Common\Collections\Collection $reponses;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->reponses = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }
    public function getRecette(): ?Recette { return $this->recette; }
    public function setRecette(?Recette $recette): self { $this->recette = $recette; return $this; }
    public function getNote(): ?int { return $this->note; }
    public function setNote(?int $note): self { $this->note = $note; return $this; }
    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $commentaire): self { $this->commentaire = $commentaire; return $this; }
    public function isSignale(): bool { return $this->signale; }
    public function setSignale(bool $signale): self { $this->signale = $signale; return $this; }
    public function getMotifSignalement(): ?string { return $this->motifSignalement; }
    public function setMotifSignalement(?string $motifSignalement): self { $this->motifSignalement = $motifSignalement; return $this; }
    public function getParentAvis(): ?self { return $this->parentAvis; }
    public function setParentAvis(?self $parentAvis): self { $this->parentAvis = $parentAvis; return $this; }
    public function getReponses(): \Doctrine\Common\Collections\Collection { return $this->reponses; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
}
