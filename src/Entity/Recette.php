<?php

namespace App\Entity;

use App\Enum\RecetteStatut;
use App\Enum\RecetteTypePlat;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'recette')]
class Recette
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    private string $titre;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(type: 'string', enumType: RecetteTypePlat::class)]
    private RecetteTypePlat $typePlat;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'recettes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::FLOAT)]
    private float $nbrPerson;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $timePrepa = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $ingredient;

    #[ORM\Column(type: Types::TEXT)]
    private string $preparation;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isOven = false;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $tempOven = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $timeOven = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isPublic = false;

    #[ORM\Column(type: 'string', enumType: RecetteStatut::class)]
    private RecetteStatut $statut = RecetteStatut::ATTENTE;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\OneToMany(mappedBy: 'recette', targetEntity: Favoris::class, cascade: ['persist', 'remove'])]
    private Collection $favoris;

    #[ORM\OneToMany(mappedBy: 'recette', targetEntity: Avis::class, cascade: ['persist', 'remove'])]
    private Collection $avis;

    #[ORM\ManyToMany(targetEntity: Product::class, inversedBy: 'recettes')]
    #[ORM\JoinTable(name: 'recette_product')]
    private Collection $products;

    #[ORM\ManyToMany(targetEntity: Season::class, inversedBy: 'recettes')]
    #[ORM\JoinTable(name: 'recette_season')]
    private Collection $seasons;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->favoris = new ArrayCollection();
        $this->avis = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->seasons = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getTitre(): string { return $this->titre; }
    public function setTitre(string $titre): self { $this->titre = $titre; return $this; }
    public function getPhoto(): ?string { return $this->photo; }
    public function setPhoto(?string $photo): self { $this->photo = $photo; return $this; }
    public function getTypePlat(): RecetteTypePlat { return $this->typePlat; }
    public function setTypePlat(RecetteTypePlat $typePlat): self { $this->typePlat = $typePlat; return $this; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }
    public function getNbrPerson(): float { return $this->nbrPerson; }
    public function setNbrPerson(float $nbrPerson): self { $this->nbrPerson = $nbrPerson; return $this; }
    public function getTimePrepa(): ?int { return $this->timePrepa; }
    public function setTimePrepa(?int $timePrepa): self { $this->timePrepa = $timePrepa; return $this; }
    public function getIngredient(): string { return $this->ingredient; }
    public function setIngredient(string $ingredient): self { $this->ingredient = $ingredient; return $this; }
    public function getPreparation(): string { return $this->preparation; }
    public function setPreparation(string $preparation): self { $this->preparation = $preparation; return $this; }
    public function isOven(): bool { return $this->isOven; }
    public function setIsOven(bool $isOven): self { $this->isOven = $isOven; return $this; }
    public function getTempOven(): ?float { return $this->tempOven; }
    public function setTempOven(?float $tempOven): self { $this->tempOven = $tempOven; return $this; }
    public function getTimeOven(): ?float { return $this->timeOven; }
    public function setTimeOven(?float $timeOven): self { $this->timeOven = $timeOven; return $this; }
    public function isPublic(): bool { return $this->isPublic; }
    public function setIsPublic(bool $isPublic): self { $this->isPublic = $isPublic; return $this; }
    public function getStatut(): RecetteStatut { return $this->statut; }
    public function setStatut(RecetteStatut $statut): self { $this->statut = $statut; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getFavoris(): Collection { return $this->favoris; }
    public function getAvis(): Collection { return $this->avis; }
    public function getProducts(): Collection { return $this->products; }
    public function addProduct(Product $product): self { if (!$this->products->contains($product)) { $this->products->add($product); } return $this; }
    public function removeProduct(Product $product): self { $this->products->removeElement($product); return $this; }
    public function getSeasons(): Collection { return $this->seasons; }
    public function addSeason(Season $season): self { if (!$this->seasons->contains($season)) { $this->seasons->add($season); } return $this; }
    public function removeSeason(Season $season): self { $this->seasons->removeElement($season); return $this; }
}
