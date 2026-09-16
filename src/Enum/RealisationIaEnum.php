<?php

namespace App\Enum;

enum RealisationIaEnum: int
{
    case NON_RENSEIGNE = 4;
    case AUCUN = 0;
    case PEU = 1;
    case MOYEN = 2;
    case AVANCE = 3;

    public function getLibelle(): string
    {
        return match($this) {
            self::AUCUN => 'Aucun usage de l\'IA',
            self::PEU => 'Quelques éléments du projet ont été générés avec l\'IA (<50%)',
            self::MOYEN => 'La plupart des éléments du projet ont été générés avec l\'IA (>50%)',
            self::AVANCE => 'J\'ai rédigé un prompt à partir des consignes ou le projet a été généré avec l\'IA (>90%)',
        };
    }
}
