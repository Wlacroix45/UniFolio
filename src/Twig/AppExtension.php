<?php

/*
 * Copyright (c) 2023. | Cyndel Herolt | IUT de Troyes  - All Rights Reserved
 * @author cyndelherolt
 * @project UniFolio
 */
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('url_sure', $this->urlSure(...)),
        ];
    }

    public function urlSure(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '' || preg_match('/[\x00-\x20\x7f]/', $url) === 1) {
            return null;
        }
        if (str_starts_with($url, 'www.')) {
            return 'https://' . $url;
        }
        if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $url) === 1 && preg_match('#^https?:#i', $url) !== 1) {
            return null;
        }

        return $url;
    }
}
