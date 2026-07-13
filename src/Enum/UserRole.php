<?php

namespace App\Enum;

enum UserRole: string
{
    case VISITEUR = 'visiteur';
    case UTILISATEUR = 'utilisateur';
    case ADMINISTRATEUR = 'administrateur';
}
