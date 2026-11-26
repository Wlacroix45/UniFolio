<?php

namespace App\Service;

use App\Entity\Etudiant;
use App\Entity\OrdrePage;
use App\Entity\Page;
use App\Entity\Portfolio;
use App\Entity\PortfolioTemplate;
use App\Enum\PortfolioTemplateStatusEnum;
use Doctrine\ORM\EntityManagerInterface;

class PortfolioTemplateSnapshotService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Génère un portfolio guidé figé (snapshot) pour un étudiant à partir d'un modèle publié.
     */
    public function createSnapshot(
        PortfolioTemplate $template,
        Etudiant $etudiant,
        bool $flush = true
    ): Portfolio {
        if ($template->getStatut() !== PortfolioTemplateStatusEnum::PUBLIE) {
            throw new \DomainException(
                'Seul un modèle publié peut être utilisé pour démarrer un portfolio guidé.'
            );
        }

        $portfolio = new Portfolio();
        $portfolio->setEtudiant($etudiant);
        $portfolio->setIntitule($template->getNom());
        $portfolio->setDescription($template->getDescription());
        $portfolio->setAnnee($template->getAnnee());
        $portfolio->setVisibilite(false);
        $portfolio->setOptSearch(false);
        $portfolio->setIsGuided(true);
        $portfolio->setPortfolioTemplate($template);
        $portfolio->setApcReferentiel($template->getApcReferentiel());
        $portfolio->setSnapshotTemplateNom($template->getNom());

        $position = 1;
        foreach ($template->getOrderedSections() as $section) {
            $page = new Page();
            $page->setIntitule($section->getTitre());
            $page->setDescription($section->getConsigne());
            $page->setConsigne($section->getConsigne());
            $page->setQuestionReflexivePrompt($section->getQuestionReflexivePrompt());
            $page->setOrdre($position);
            $page->setTemplateSection($section);

            $competences = !$section->getCompetencesCibles()->isEmpty()
                ? $section->getCompetencesCibles()
                : $template->getCompetencesCibles();

            foreach ($competences as $niveau) {
                $page->addCompetenceCible($niveau);
            }

            $portfolio->addPage($page);

            $ordrePage = new OrdrePage();
            $ordrePage->setPortfolio($portfolio);
            $ordrePage->setPage($page);
            $ordrePage->setOrdre($position);
            $portfolio->addOrdrePage($ordrePage);
            $page->setOrdrePage($ordrePage);

            $this->entityManager->persist($page);
            $this->entityManager->persist($ordrePage);
            ++$position;
        }

        $this->entityManager->persist($portfolio);

        if ($flush) {
            $this->entityManager->flush();
        }

        return $portfolio;
    }
}
