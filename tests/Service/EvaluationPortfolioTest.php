<?php

namespace App\Tests\Service;

use App\Entity\OrdrePage;
use App\Entity\OrdreTrace;
use App\Entity\Page;
use App\Entity\Portfolio;
use App\Entity\Trace;
use App\Entity\Validation;
use App\Service\EvaluationPortfolio;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EvaluationPortfolioTest extends TestCase
{
    private EvaluationPortfolio $service;

    protected function setUp(): void
    {
        $this->service = new EvaluationPortfolio(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(EventDispatcherInterface::class)
        );
    }

    /** @return array{Portfolio, Validation, Validation} */
    private function creerPortfolio(): array
    {
        $trace = new Trace();
        $validations = [];
        foreach ([11, 12] as $id) {
            $validation = (new Validation())->setEtat(0);
            (new \ReflectionProperty(Validation::class, 'id'))->setValue($validation, $id);
            $trace->addValidation($validation);
            $validations[] = $validation;
        }
        $page = (new Page())->addOrdreTrace((new OrdreTrace())->setTrace($trace)->setOrdre(1));
        $portfolio = (new Portfolio())->addOrdrePage((new OrdrePage())->setPage($page)->setOrdre(1));

        return [$portfolio, ...$validations];
    }

    public function testLeBrouillonNeValidePas(): void
    {
        [$portfolio, $premiere] = $this->creerPortfolio();

        $this->service->enregistrer($portfolio, [11 => '3'], null);

        $this->assertSame(3, $premiere->isEtat());
        $this->assertNotNull($portfolio->getDateBrouillonEvaluation());
        $this->assertFalse($portfolio->isEvaluationValidee());
    }

    public function testUneGrilleIncompleteNePeutPasEtreValidee(): void
    {
        [$portfolio, $premiere] = $this->creerPortfolio();

        try {
            $this->service->valider($portfolio, [11 => 3], null);
            $this->fail('La validation aurait dû être refusée.');
        } catch (\DomainException) {
        }

        $this->assertSame(0, $premiere->isEtat(), 'Rien ne doit être modifié.');
        $this->assertFalse($portfolio->isEvaluationValidee());
    }

    public function testUneEvaluationValideeEstVerrouillee(): void
    {
        [$portfolio] = $this->creerPortfolio();
        $this->service->valider($portfolio, [11 => 3, 12 => 2], null);
        $this->assertTrue($portfolio->isEvaluationValidee());

        $this->expectException(\DomainException::class);
        $this->service->enregistrer($portfolio, [11 => 1], null);
    }

    public function testUneLigneEtrangereAuPortfolioEstRefusee(): void
    {
        [$portfolio] = $this->creerPortfolio();

        $this->expectException(\DomainException::class);
        $this->service->enregistrer($portfolio, [999 => 3], null);
    }

    public function testUnNiveauInconnuEstRefuse(): void
    {
        [$portfolio] = $this->creerPortfolio();

        $this->expectException(\DomainException::class);
        $this->service->enregistrer($portfolio, [11 => 7], null);
    }
}
