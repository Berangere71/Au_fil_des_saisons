<?php

namespace App\Enum;

enum SeasonName: string
{
    case PRINTEMPS = 'printemps';
    case ETE = 'ete';
    case AUTOMNE = 'automne';
    case HIVER = 'hiver';

    public static function fromDate(\DateTimeInterface $date): self
    {
        $monthAndDay = (int) $date->format('md');

        return match (true) {
            $monthAndDay >= 320 && $monthAndDay <= 620 => self::PRINTEMPS,
            $monthAndDay >= 621 && $monthAndDay <= 922 => self::ETE,
            $monthAndDay >= 923 && $monthAndDay <= 1221 => self::AUTOMNE,
            default => self::HIVER,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::PRINTEMPS => 'Printemps',
            self::ETE => 'Été',
            self::AUTOMNE => 'Automne',
            self::HIVER => 'Hiver',
        };
    }

    public function periodLabel(): string
    {
        return match ($this) {
            self::PRINTEMPS => 'du 20 mars au 20 juin',
            self::ETE => 'du 21 juin au 22 septembre',
            self::AUTOMNE => 'du 23 septembre au 21 décembre',
            self::HIVER => 'du 22 décembre au 19 mars',
        };
    }

    /**
     * Mois touchés par la saison, pour les produits dont la récolte est saisie au mois.
     *
     * @return list<int>
     */
    public function months(): array
    {
        return match ($this) {
            self::PRINTEMPS => [3, 4, 5, 6],
            self::ETE => [6, 7, 8, 9],
            self::AUTOMNE => [9, 10, 11, 12],
            self::HIVER => [12, 1, 2, 3],
        };
    }
}
