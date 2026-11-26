<?php

namespace App\Enum;

enum PortfolioTemplateStatusEnum: string
{
    case BROUILLON = 'brouillon';
    case PUBLIE = 'publie';
    case ARCHIVE = 'archive';

    public function getLibelle(): string
    {
        return match ($this) {
            self::BROUILLON => 'Brouillon',
            self::PUBLIE => 'Publié',
            self::ARCHIVE => 'Archivé',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::BROUILLON => 'bg-secondary',
            self::PUBLIE => 'bg-success',
            self::ARCHIVE => 'bg-dark',
        };
    }
}
