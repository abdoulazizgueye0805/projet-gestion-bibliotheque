<?php

class SimulateurEmprunt
{
    public static function calculerInterets(float $montant, float $tauxAnnuel, int $dureeMois): float
    {
        return ($montant * $tauxAnnuel * $dureeMois) / 1200;
    }

    public static function calculerCoutTotal(float $montant, float $tauxAnnuel, int $dureeMois): float
    {
        return $montant + self::calculerInterets($montant, $tauxAnnuel, $dureeMois);
    }
}
