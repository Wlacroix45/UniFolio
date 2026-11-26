<?php

namespace App\Tests\Service;

use App\Entity\Annee;
use App\Entity\ApcNiveau;
use App\Entity\ApcReferentiel;
use App\Entity\Departement;
use App\Entity\Etudiant;
use App\Entity\Page;
use App\Entity\PortfolioTemplate;
use App\Entity\TemplateSection;
use App\Service\PortfolioTemplateSnapshotService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class PortfolioTemplateSnapshotServiceTest extends TestCase
{
    public function testCreateSnapshotRejectsDraftTemplate(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $service = new PortfolioTemplateSnapshotService($em);
        $draft = (new PortfolioTemplate())->setNom('Brouillon');
        $draft->addSection((new TemplateSection())->setOrdre(1)->setTitre('Section 1'));

        $this->expectException(\DomainException::class);
        $service->createSnapshot($draft, new Etudiant());
    }

    public function testCreateSnapshotRejectsArchivedTemplate(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $service = new PortfolioTemplateSnapshotService($em);
        $archived = (new PortfolioTemplate())->setNom('Archivé');
        $archived->archive();

        $this->expectException(\DomainException::class);
        $service->createSnapshot($archived, new Etudiant());
    }

    public function testCreateSnapshotCopiesTemplateAndSections(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->atLeastOnce())->method('persist');
        $em->expects($this->once())->method('flush');

        $service = new PortfolioTemplateSnapshotService($em);

        $departement = (new Departement())->setId(1)->setLibelle('INFO');
        $referentiel = (new ApcReferentiel())->setId(10)->setLibelle('PN BUT INFO 2023')->setDepartement($departement);
        $annee = (new Annee())->setId(2)->setLibelle('BUT2');

        $nivGlobal = (new ApcNiveau())->setId(100)->setLibelle('Développer des applications');
        $nivSpecifique = (new ApcNiveau())->setId(101)->setLibelle('Conduire un projet');

        $template = (new PortfolioTemplate())
            ->setNom('Portfolio de stage BUT2')
            ->setDescription('Consigne globale du stage')
            ->setDepartement($departement)
            ->setApcReferentiel($referentiel)
            ->setAnnee($annee)
            ->addCompetenceCible($nivGlobal);

        $sec2 = (new TemplateSection())
            ->setOrdre(2)
            ->setTitre('Bilan final')
            ->setConsigne('Synthèse des apprentissages')
            ->setQuestionReflexivePrompt('Quels axes d\'amélioration identifiez-vous ?');

        $sec1 = (new TemplateSection())
            ->setOrdre(1)
            ->setTitre('Analyse réflexive 1')
            ->setConsigne('Décrire une réalisation')
            ->setQuestionReflexivePrompt('Pourquoi avoir choisi cette approche ?')
            ->addCompetenceCible($nivSpecifique);

        $template->addSection($sec2);
        $template->addSection($sec1);
        $template->publish();

        $etudiant = new Etudiant();
        $portfolio = $service->createSnapshot($template, $etudiant);

        $this->assertTrue($portfolio->isGuided());
        $this->assertSame($etudiant, $portfolio->getEtudiant());
        $this->assertSame($template, $portfolio->getPortfolioTemplate());
        $this->assertSame($referentiel, $portfolio->getApcReferentiel());
        $this->assertSame($annee, $portfolio->getAnnee());
        $this->assertSame('Portfolio de stage BUT2', $portfolio->getIntitule());
        $this->assertSame('Portfolio de stage BUT2', $portfolio->getSnapshotTemplateNom());
        $this->assertSame('Consigne globale du stage', $portfolio->getDescription());
        $this->assertFalse($portfolio->isVisibilite());
        $this->assertFalse($portfolio->isOptSearch());

        $ordrePages = $portfolio->getOrdrePages()->toArray();
        $this->assertCount(2, $ordrePages);
        $this->assertSame(1, $ordrePages[0]->getOrdre());
        $this->assertSame('Analyse réflexive 1', $ordrePages[0]->getPage()?->getIntitule());
        $this->assertSame('Décrire une réalisation', $ordrePages[0]->getPage()?->getConsigne());
        $this->assertSame(
            'Pourquoi avoir choisi cette approche ?',
            $ordrePages[0]->getPage()?->getQuestionReflexivePrompt()
        );
        $this->assertSame([$nivSpecifique], $ordrePages[0]->getPage()?->getCompetencesCibles()->toArray());

        // Section 2 sans compétence spécifique -> hérite du modèle
        $this->assertSame(2, $ordrePages[1]->getOrdre());
        $this->assertSame('Bilan final', $ordrePages[1]->getPage()?->getIntitule());
        $this->assertSame([$nivGlobal], $ordrePages[1]->getPage()?->getCompetencesCibles()->toArray());
    }

    public function testSnapshotIsIndependentFromTemplateChanges(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $service = new PortfolioTemplateSnapshotService($em);

        $template = (new PortfolioTemplate())
            ->setNom('Portfolio de stage BUT2')
            ->setDescription('Consigne globale du stage');

        $sec1 = (new TemplateSection())->setOrdre(1)->setTitre('Analyse réflexive 1')->setConsigne('Consigne 1');
        $sec2 = (new TemplateSection())->setOrdre(2)->setTitre('Bilan final')->setConsigne('Consigne 2');
        $template->addSection($sec1)->addSection($sec2);
        $template->publish();

        $portfolio = $service->createSnapshot($template, new Etudiant());
        $ordrePages = $portfolio->getOrdrePages()->toArray();

        // Modifications et archivage après génération du snapshot
        $template->setNom('Modèle renommé');
        $template->setDescription('Nouvelle description');
        $sec1->setTitre('Titre modifié');
        $sec1->setConsigne('Consigne modifiée');
        $template->removeSection($sec2);
        $template->addSection((new TemplateSection())->setOrdre(3)->setTitre('Nouvelle section'));
        $template->archive();

        $this->assertSame('Portfolio de stage BUT2', $portfolio->getIntitule());
        $this->assertSame('Portfolio de stage BUT2', $portfolio->getSnapshotTemplateNom());
        $this->assertSame('Consigne globale du stage', $portfolio->getDescription());
        $this->assertCount(2, $portfolio->getPages());

        /** @var Page $firstPage */
        $firstPage = $ordrePages[0]->getPage();
        /** @var Page $secondPage */
        $secondPage = $ordrePages[1]->getPage();
        $this->assertSame('Analyse réflexive 1', $firstPage->getIntitule());
        $this->assertSame('Consigne 1', $firstPage->getConsigne());
        $this->assertSame('Bilan final', $secondPage->getIntitule());
        $this->assertSame('Consigne 2', $secondPage->getConsigne());
    }
}
