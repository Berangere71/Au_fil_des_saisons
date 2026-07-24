<?php

namespace App\Entity;

use App\Enum\ProductCategory;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'product')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private string $nom;

    #[ORM\Column(type: 'string', enumType: ProductCategory::class)]
    private ProductCategory $category;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\ManyToOne(targetEntity: Month::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Month $debutRecolteMois = null;

    #[ORM\ManyToOne(targetEntity: Month::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Month $finRecolteMois = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $conservation;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\ManyToMany(targetEntity: Recette::class, mappedBy: 'products')]
    private Collection $recettes;

    #[ORM\ManyToMany(targetEntity: Season::class, inversedBy: 'products')]
    #[ORM\JoinTable(name: 'product_season')]
    private Collection $seasons;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->recettes = new ArrayCollection();
        $this->seasons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getNom(): string
    {
        return $this->nom;
    }
    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }
    public function getCategory(): ProductCategory
    {
        return $this->category;
    }
    public function setCategory(ProductCategory $category): self
    {
        $this->category = $category;
        return $this;
    }
    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;
        return $this;
    }
    public function getDebutRecolteMois(): ?Month
    {
        return $this->debutRecolteMois;
    }
    public function setDebutRecolteMois(?Month $debutRecolteMois): self
    {
        $this->debutRecolteMois = $debutRecolteMois;
        return $this;
    }
    public function getFinRecolteMois(): ?Month
    {
        return $this->finRecolteMois;
    }
    public function setFinRecolteMois(?Month $finRecolteMois): self
    {
        $this->finRecolteMois = $finRecolteMois;
        return $this;
    }
    public function getConservation(): string
    {
        return $this->conservation;
    }
    public function setConservation(string $conservation): self
    {
        $this->conservation = $conservation;
        return $this;
    }
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
    public function getRecettes(): Collection
    {
        return $this->recettes;
    }
    public function getSeasons(): Collection
    {
        return $this->seasons;
    }
    public function addSeason(Season $season): self
    {
        if (!$this->seasons->contains($season)) {
            $this->seasons->add($season);
        }
        return $this;
    }
    public function removeSeason(Season $season): self
    {
        $this->seasons->removeElement($season);
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
