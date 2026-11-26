<?php

namespace App\Enum;

enum EvaluationStatutEnum: string
{
    case A_EVALUER = 'a_evaluer';
    case EN_COURS = 'en_cours';
    case EVALUE = 'evalue';

    public function getLibelle(): string
    {
        return match ($this) {
            self::A_EVALUER => 'À évaluer',
            self::EN_COURS => 'En cours',
            self::EVALUE => 'Évalués',
        };
    }

    public function getLibelleSingulier(): string
    {
        return $this === self::EVALUE ? 'Évalué' : $this->getLibelle();
    }
}
