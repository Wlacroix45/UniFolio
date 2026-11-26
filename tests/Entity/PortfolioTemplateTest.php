<?php

namespace App\Tests\Entity;

use App\Entity\PortfolioTemplate;
use App\Entity\TemplateSection;
use App\Enum\PortfolioTemplateStatusEnum;
use PHPUnit\Framework\TestCase;

class PortfolioTemplateTest extends TestCase
{
    public function testDefaultStatusIsDraft(): void
    {
        $template = new PortfolioTemplate();

        $this->assertSame(PortfolioTemplateStatusEnum::BROUILLON, $template->getStatut());
        $this->assertTrue($template->isDraft());
        $this->assertFalse($template->isPublished());
        $this->assertFalse($template->isArchived());
    }

    public function testPublishRequiresAtLeastOneSection(): void
    {
        $template = (new PortfolioTemplate())->setNom('Stage BUT2');

        $this->expectException(\LogicException::class);
        $template->publish();
    }

    public function testLifecycleDraftToPublishedToArchived(): void
    {
        $template = (new PortfolioTemplate())->setNom('Stage BUT2');
        $section = (new TemplateSection())->setOrdre(1)->setTitre('Introduction');
        $template->addSection($section);

        $template->publish();
        $this->assertSame(PortfolioTemplateStatusEnum::PUBLIE, $template->getStatut());
        $this->assertTrue($template->isPublished());

        $template->archive();
        $this->assertSame(PortfolioTemplateStatusEnum::ARCHIVE, $template->getStatut());
        $this->assertTrue($template->isArchived());
    }

    public function testPublishAlreadyPublishedThrowsException(): void
    {
        $template = (new PortfolioTemplate())->setNom('Stage BUT2');
        $template->addSection((new TemplateSection())->setOrdre(1)->setTitre('Intro'));
        $template->publish();

        $this->expectException(\LogicException::class);
        $template->publish();
    }

    public function testArchiveAlreadyArchivedThrowsException(): void
    {
        $template = (new PortfolioTemplate())->setNom('Stage BUT2');
        $template->archive();

        $this->expectException(\LogicException::class);
        $template->archive();
    }

    public function testGetOrderedSectionsSortsByOrdre(): void
    {
        $template = new PortfolioTemplate();
        $s3 = (new TemplateSection())->setOrdre(3)->setTitre('Bilan');
        $s1 = (new TemplateSection())->setOrdre(1)->setTitre('Contexte');
        $s2 = (new TemplateSection())->setOrdre(2)->setTitre('Missions');

        $template->addSection($s3);
        $template->addSection($s1);
        $template->addSection($s2);

        $ordered = $template->getOrderedSections();
        $this->assertSame(['Contexte', 'Missions', 'Bilan'], array_map(
            static fn (TemplateSection $s): ?string => $s->getTitre(),
            $ordered
        ));
    }
}
