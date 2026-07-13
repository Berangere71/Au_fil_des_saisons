<?php

namespace App\Enum;

enum ProductCategory: string
{
    case LEGUME = 'legume';
    case FRUIT = 'fruit';
    case VIANDE = 'viande';
    case POISSON = 'poisson';
}
