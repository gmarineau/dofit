<?php

namespace App\Enums;

enum SequenceUnit: string
{
    case Kg = 'kg';
    case Min = 'min';
    case H = 'h';

    /**
     * Get the label displayed next to a sequence value.
     */
    public function label(): string
    {
        return $this->value;
    }
}
