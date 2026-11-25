<?php

namespace App\Tests\Service;

use App\Entity\Annee;
use App\Entity\ApcNiveau;
use App\Entity\ApcReferentiel;
use App\Entity\Departement;
use App\Entity\Enseignant;
use App\Entity\Etudiant;
use App\Entity\Page;
use App\Entity\PortfolioTemplate;
use App\Entity\TemplateSection;
use App\Enum\PortfolioTemplateStatusEnum;
use App\Service\PortfolioTemplateSnapshotService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class PortfolioTemplateSnapshotServiceTest extends TestCase
{
    public function testCreateSnapshotRejectsDraftAndArchivedTemplates(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $service = new PortfolioTemplateSnapshotService($em);
        $etudiant = new Etudiant();

        $draft = (new PortfolioTemplate())->setNom('Brouillon');
        $draft->addSection((new TemplateSection())->setOrdre(1)->setTitre('Section 1'));

        try {
            $service->createSnapshot($draft, $etudiant);
            $this->fail('Un brouillon ne devrait pas pouvoir générer un snapshot.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('publié', $e->getMessage());
        }

        $archived = (new PortfolioTemplate())->setNom('Archivé');
        $archived->archive();

        $this->expectException(\DomainException::class);
        $service->createSnapshot($archived, $etudiant);
    }

    public function testCreateSnapshotCreatesFrozenGuidedPortfolioDecoupledFromFutureTemplateChanges(): void
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
        $this->assertSame('Pourquoi avoir choisi cette approche ?', $ordrePages[0]->getPage()?->getQuestionReflexivePrompt());
        $this->assertSame([$nivSpecifique], $ordrePages[0]->getPage()?->getCompetencesCibles()->toArray());

        // Section 2 sans compétence spécifique -> hérite de la compétence du modèle
        $this->assertSame(2, $ordrePages[1]->getOrdre());
        $this->assertSame('Bilan final', $ordrePages[1]->getPage()?->getIntitule());
        $this->assertSame([$nivGlobal], $ordrePages[1]->getPage()?->getCompetencesCibles()->toArray());

        // Modification et archivage ultérieurs du modèle -> aucun impact sur le snapshot existant
        $template->setNom('Modèle renommé après coup');
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
        $this->assertSame('Décrire une réalisation', $firstPage->getConsigne());
        $this->assertSame('Bilan final', $secondPage->getIntitule());
        $this->assertSame('Synthèse des apprentissages', $secondPage->getConsigne());
    }

    public function testDuplicateAsDraftCreatesIndependentDraftCopy(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $service = new PortfolioTemplateSnapshotService($em);

        $departement = (new Departement())->setId(1)->setLibelle('INFO');
        $auteur = new Enseignant();
        $niveau = (new ApcNiveau())->setId(50)->setLibelle('Compétence 1');

        $source = (new PortfolioTemplate())
            ->setNom('Portfolio S4')
            ->setDescription('Description originale')
            ->setDepartement($departement)
            ->addCompetenceCible($niveau);

        $source->addSection(
            (new TemplateSection())
                ->setOrdre(1)
                ->setTitre('Étape 1')
                ->setConsigne('Consigne 1')
                ->addCompetenceCible($niveau)
        );
        $source->publish();
        $source->archive();

        $copy = $service->duplicateAsDraft($source, $auteur);

        $this->assertNotSame($source, $copy);
        $this->assertSame(PortfolioTemplateStatusEnum::BROUILLON, $copy->getStatut());
        $this->assertSame('Portfolio S4 (copie)', $copy->getNom());
        $this->assertSame($departement, $copy->getDepartement());
        $this->assertSame($auteur, $copy->getEnseignant());
        $this->assertCount(1, $copy->getCompetencesCibles());
        $this->assertCount(1, $copy->getSections());

        $copiedSection = $copy->getSections()->first();
        $this->assertInstanceOf(TemplateSection::class, $copiedSection);
        $this->assertSame('Étape 1', $copiedSection->getTitre());
        $this->assertSame($copy, $copiedSection->getPortfolioTemplate());
    }
}
