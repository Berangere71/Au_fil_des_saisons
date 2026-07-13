<?php

namespace App\Enum;

enum UserRole: string
{

    case UTILISATEUR = 'utilisateur';
    case ADMINISTRATEUR = 'administrateur';
}
