<?php

namespace App\Enums;

enum CategoryColor: string
{
    case Splash = 'splash';
    case Brand = 'brand';
    case Sun = 'sun';

    /**
     * The palette rotates by creation order so neighboring category cards
     * get distinct colors.
     */
    public static function forIndex(int $index): self
    {
        return self::cases()[$index % count(self::cases())];
    }
}
