<?php

namespace App\Enums;

enum ProductSize: string
{
    case Large = 'large';
    case Medium = 'medium';
    case Small = 'small';

    public function label(): string
    {
        return match ($this) {
            self::Large => 'Lielās',
            self::Medium => 'Vidējās',
            self::Small => 'Mazās',
        };
    }
}
