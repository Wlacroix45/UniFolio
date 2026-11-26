<?php

namespace App\Tests\Twig;

use App\Twig\AppExtension;
use PHPUnit\Framework\TestCase;

class AppExtensionTest extends TestCase
{
    public function testLeTexteEtudiantPerdScriptsEtAttributs(): void
    {
        $html = (new AppExtension())->texteEtudiant(
            '<p onclick="x()">Été <strong>réussi</strong></p><script>alert(1)</script><img src=x onerror=alert(1)>'
        );

        $this->assertSame('<p>Été <strong>réussi</strong></p>', $html);
    }

    public function testSeulsLesLiensSursSontConserves(): void
    {
        $extension = new AppExtension();

        $this->assertNull($extension->urlSure('javascript:alert(1)'));
        $this->assertNull($extension->urlSure('data:text/html,x'));
        $this->assertSame('https://www.example.org', $extension->urlSure('www.example.org'));
        $this->assertSame('files-directory/a.png', $extension->urlSure('files-directory/a.png'));
        $this->assertSame('<a>x</a>', $extension->texteEtudiant('<a href="javascript:alert(1)">x</a>'));
    }
}
