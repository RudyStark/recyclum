<?php

// src/Enum/EnergyLabel.php
namespace App\Enum;

enum EnergyLabel: string
{
    case A='A'; case B='B'; case C='C'; case D='D'; case E='E'; case F='F'; case G='G'; case NA='NA';

    /** Pour les formulaires (valeur = objet Enum) */
    public static function formChoices(): array
    {
        // libellé => objet Enum
        $out = [];
        foreach (self::cases() as $c) {
            $out[$c->value] = $c;   // ex. 'A' => EnergyLabel::A
        }
        return $out;
    }

    /** Pour les filtres (valeur = string en BDD) */
    public static function filterChoices(): array
    {
        // libellé => string (stocké en DB)
        $out = [];
        foreach (self::cases() as $c) {
            $out[$c->value] = $c->value; // ex. 'A' => 'A'
        }
        return $out;
    }
}
