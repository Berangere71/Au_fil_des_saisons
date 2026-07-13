<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'favoris')]
#[ORM\UniqueConstraint(name: 'uniq_favoris_user_recette', columns: ['user_id', 'recette_id'])]
class Favoris
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'favoris')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Recette::class, inversedBy: 'favoris')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recette $recette = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dateAjout;

    public function __construct()
    {
        $this->dateAjout = new \DateTimeImmutable();
    }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }
    public function getRecette(): ?Recette { return $this->recette; }
    public function setRecette(?Recette $recette): self { $this->recette = $recette; return $this; }
    public function getDateAjout(): \DateTimeInterface { return $this->dateAjout; }
}
