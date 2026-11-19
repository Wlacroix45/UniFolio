<?php

namespace App\Enum;

enum DepartementRoleEnum: string
{
    case EVALUATEUR = 'ROLE_EVALUATEUR';
    case EDITEUR = 'ROLE_EDITEUR';

    public function getLibelle(): string
    {
        return match ($this) {
            self::EVALUATEUR => 'Évaluateur',
            self::EDITEUR => 'Éditeur',
        };
    }

    /**
     * @return array<string, self>
     */
    public static function choices(): array
    {
        return [
            self::EVALUATEUR->getLibelle() => self::EVALUATEUR,
            self::EDITEUR->getLibelle() => self::EDITEUR,
        ];
    }
}
