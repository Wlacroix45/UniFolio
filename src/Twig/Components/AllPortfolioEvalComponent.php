<?php

/*
 * Copyright (c) 2023. | Cyndel Herolt | IUT de Troyes  - All Rights Reserved
 * @author cyndelherolt
 * @project UniFolio
 */

namespace App\Twig\Components;

use App\Controller\BaseController;
use App\Entity\Groupe;
use App\Entity\Portfolio;
use App\Enum\EvaluationStatutEnum;
use App\Repository\GroupeRepository;
use App\Repository\PortfolioRepository;
use App\Repository\SemestreRepository;
use App\Security\Voter\DepartementVoter;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent('AllPortfolioEvalComponent')]
final class AllPortfolioEvalComponent extends BaseController
{
    use DefaultActionTrait;

    private const PAR_PAGE = 20;

    #[LiveProp(writable: true, url: true)]
    public string $statut = 'a_evaluer';

    #[LiveProp(writable: true, onUpdated: 'revenirAuDebut')]
    public int $semestreId = 0;

    #[LiveProp(writable: true, onUpdated: 'revenirAuDebut')]
    public int $groupeId = 0;

    #[LiveProp(writable: true, onUpdated: 'revenirAuDebut')]
    public int $etudiantId = 0;

    #[LiveProp(writable: true, onUpdated: 'revenirAuDebut')]
    public int $niveauId = 0;

    #[LiveProp(writable: true)]
    public int $page = 1;

    private ?array $compteurs = null;

    public function __construct(
        private readonly PortfolioRepository $portfolioRepository,
        private readonly SemestreRepository $semestreRepository,
        private readonly GroupeRepository $groupeRepository,
    ) {
    }

    #[PostMount]
    #[PostHydrate]
    public function controlerAcces(): void
    {
        $departement = $this->dataUserSession->getDepartement();
        if ($departement !== null) {
            $this->denyAccessUnlessGranted(DepartementVoter::VIEW, $departement);
        }
    }

    public function revenirAuDebut(): void
    {
        $this->page = 1;
    }

    #[LiveAction]
    public function changerStatut(#[LiveArg] string $statut): void
    {
        $this->statut = (EvaluationStatutEnum::tryFrom($statut) ?? EvaluationStatutEnum::A_EVALUER)->value;
        $this->page = 1;
    }

    #[LiveAction]
    public function allerPage(#[LiveArg] int $page): void
    {
        $this->page = $page;
    }

    #[LiveAction]
    public function reinitialiser(): void
    {
        $this->semestreId = $this->groupeId = $this->etudiantId = $this->niveauId = 0;
        $this->page = 1;
    }

    public function getStatutActif(): EvaluationStatutEnum
    {
        return EvaluationStatutEnum::tryFrom($this->statut) ?? EvaluationStatutEnum::A_EVALUER;
    }

    public function getStatuts(): array
    {
        return EvaluationStatutEnum::cases();
    }

    public function getCompteurs(): array
    {
        if ($this->compteurs !== null) {
            return $this->compteurs;
        }

        $departement = $this->dataUserSession->getDepartement();
        $this->compteurs = [];
        foreach (EvaluationStatutEnum::cases() as $statut) {
            $this->compteurs[$statut->value] = $departement === null
                ? 0
                : $this->portfolioRepository->countForEvaluation($departement, $statut, $this->getFiltres());
        }

        return $this->compteurs;
    }

    public function getNombrePages(): int
    {
        return max(1, (int) ceil($this->getCompteurs()[$this->getStatutActif()->value] / self::PAR_PAGE));
    }

    public function getPageCourante(): int
    {
        return min(max(1, $this->page), $this->getNombrePages());
    }

    public function getLignes(): array
    {
        $departement = $this->dataUserSession->getDepartement();
        if ($departement === null) {
            return [];
        }

        $portfolios = $this->portfolioRepository->findForEvaluation(
            $departement,
            $this->getStatutActif(),
            $this->getFiltres(),
            self::PAR_PAGE,
            ($this->getPageCourante() - 1) * self::PAR_PAGE
        );
        $progression = $this->portfolioRepository->findProgression(
            array_map(static fn (Portfolio $p) => (int) $p->getId(), $portfolios)
        );

        return array_map(static fn (Portfolio $p) => [
            'portfolio' => $p,
            ...($progression[$p->getId()] ?? ['total' => 0, 'evaluees' => 0]),
        ], $portfolios);
    }

    public function getSemestres(): array
    {
        $departement = $this->dataUserSession->getDepartement();

        return $departement === null ? [] : $this->semestreRepository->findByDepartementActif($departement);
    }

    public function getGroupes(): array
    {
        $departement = $this->dataUserSession->getDepartement();
        if ($departement === null) {
            return [];
        }

        return array_values(array_filter(
            $this->groupeRepository->findByDepartementSemestreActif($departement),
            static fn (Groupe $groupe) => $groupe->getTypeGroupe()?->getType() === 'TP'
        ));
    }

    public function getEtudiants(): array
    {
        $departement = $this->dataUserSession->getDepartement();

        return $departement === null ? [] : $this->portfolioRepository->findEtudiantsAvecPortfolio($departement);
    }

    public function getNiveaux(): array
    {
        $departement = $this->dataUserSession->getDepartement();

        return $departement === null ? [] : $this->portfolioRepository->findNiveauxRevendiques($departement);
    }

    public function isFiltre(): bool
    {
        return array_filter($this->getFiltres()) !== [];
    }

    private function getFiltres(): array
    {
        return [
            'semestre' => $this->semestreId,
            'groupe' => $this->groupeId,
            'etudiant' => $this->etudiantId,
            'niveau' => $this->niveauId,
        ];
    }
}
