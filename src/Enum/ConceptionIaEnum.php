<?php

namespace App\Enum;

enum ConceptionIaEnum: int
{
    case NON_RENSEIGNE = 3;
    case AUCUN = 0;
    case MOYEN = 1;
    case AVANCE = 2;

    public function getLibelle(): string
    {
        return match($this) {
            self::AUCUN => 'Aucun usage de l\'IA',
            self::MOYEN => 'Je me suis aidé de l\'IA pour réfléchir à partir de mes idées',
            self::AVANCE => 'J\'ai rédigé un prompt à partir des consignes pour trouver des idées',
        };
    }
}
