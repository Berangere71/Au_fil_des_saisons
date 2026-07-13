<?php

namespace App\Entity;

use App\Enum\SeasonName;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'season')]
class Season
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: 'string', enumType: SeasonName::class, unique: true)]
    private SeasonName $nameSeason;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\ManyToMany(targetEntity: Recette::class, mappedBy: 'seasons')]
    private Collection $recettes;

    #[ORM\ManyToMany(targetEntity: Product::class, mappedBy: 'seasons')]
    private Collection $products;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->recettes = new ArrayCollection();
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getNameSeason(): SeasonName { return $this->nameSeason; }
    public function setNameSeason(SeasonName $nameSeason): self { $this->nameSeason = $nameSeason; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getRecettes(): Collection { return $this->recettes; }
    public function getProducts(): Collection { return $this->products; }
}
