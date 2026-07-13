<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'month')]
class Month
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank]
    private string $nameMonth;

    #[ORM\Column(type: Types::INTEGER, unique: true)]
    private int $monthOrder;

    public function getId(): ?int { return $this->id; }
    public function getNameMonth(): string { return $this->nameMonth; }
    public function setNameMonth(string $nameMonth): self { $this->nameMonth = $nameMonth; return $this; }
    public function getMonthOrder(): int { return $this->monthOrder; }
    public function setMonthOrder(int $monthOrder): self { $this->monthOrder = $monthOrder; return $this; }
}
