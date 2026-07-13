<?php

namespace App\Enum;

enum RecetteStatut: string
{
    case ATTENTE = 'attente';
    case PUBLIEE = 'publiee';
    case REJETEE = 'rejetee';
    case SIGNALEE = 'signalee';
}
