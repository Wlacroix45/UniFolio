<?php

namespace App\Service;

use App\Entity\Enseignant;
use App\Entity\Page;
use App\Entity\Portfolio;
use App\Entity\Trace;
use App\Entity\Validation;
use App\Event\EvaluationEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EvaluationPortfolio
{
    public const NIVEAUX = [
        0 => 'Non évaluée',
        1 => 'Non acquis',
        2 => 'En cours',
        3 => 'Acquis',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function getParties(Portfolio $portfolio): array
    {
        $ordrePages = $portfolio->getOrdrePages()->toArray();
        usort($ordrePages, static fn ($a, $b) => $a->getOrdre() <=> $b->getOrdre());

        $parties = [];
        foreach ($ordrePages as $ordrePage) {
            $page = $ordrePage->getPage();
            if ($page === null) {
                continue;
            }

            $ordreTraces = $page->getOrdreTraces()->toArray();
            usort($ordreTraces, static fn ($a, $b) => $a->getOrdre() <=> $b->getOrdre());

            $traces = [];
            foreach ($ordreTraces as $ordreTrace) {
                if ($ordreTrace->getTrace() !== null) {
                    $traces[] = $ordreTrace->getTrace();
                }
            }

            $parties[] = ['page' => $page, 'traces' => $traces];
        }

        return $parties;
    }

    public function getValidations(Portfolio $portfolio): array
    {
        $validations = [];
        foreach ($this->getParties($portfolio) as $partie) {
            foreach ($partie['traces'] as $trace) {
                foreach ($trace->getValidations() as $validation) {
                    $validations[(int) $validation->getId()] = $validation;
                }
            }
        }

        return $validations;
    }

    public function enregistrer(Portfolio $portfolio, array $etats, ?Enseignant $enseignant): void
    {
        $this->verifierModifiable($portfolio);
        $this->appliquer($this->getValidations($portfolio), $this->normaliser($portfolio, $etats), $enseignant);

        $portfolio->setDateBrouillonEvaluation(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function valider(Portfolio $portfolio, array $etats, ?Enseignant $enseignant): void
    {
        $this->verifierModifiable($portfolio);

        $validations = $this->getValidations($portfolio);
        $etats = $this->normaliser($portfolio, $etats);

        if ($validations === []) {
            throw new \DomainException('Ce portfolio ne revendique aucune compétence : il n\'y a rien à valider.');
        }

        $manquantes = 0;
        foreach ($validations as $id => $validation) {
            if (($etats[$id] ?? (int) $validation->isEtat()) === 0) {
                ++$manquantes;
            }
        }
        if ($manquantes > 0) {
            throw new \DomainException(sprintf(
                'Validation impossible : %d compétence(s) restent à évaluer. Utilisez « Enregistrer » pour conserver un brouillon.',
                $manquantes
            ));
        }

        $this->appliquer($validations, $etats, $enseignant);

        $maintenant = new \DateTimeImmutable();
        $portfolio->setDateBrouillonEvaluation($maintenant);
        $portfolio->setDateValidationEvaluation($maintenant);
        $portfolio->setValidateurEvaluation($enseignant);
        $this->entityManager->flush();

        foreach ($validations as $validation) {
            if ($validation->getTrace()?->getBibliotheque()?->getEtudiant() !== null) {
                $this->eventDispatcher->dispatch(new EvaluationEvent($validation), EvaluationEvent::EVALUATED);
            }
        }
    }

    public function isTraceVerrouillee(?Trace $trace): bool
    {
        $portfolio = $trace?->getOrdreTrace()?->getPage()?->getOrdrePage()?->getPortfolio();

        return $portfolio?->isEvaluationValidee() ?? false;
    }

    private function verifierModifiable(Portfolio $portfolio): void
    {
        if ($portfolio->isEvaluationValidee()) {
            throw new \DomainException('L\'évaluation de ce portfolio est validée et ne peut plus être modifiée.');
        }
    }

    private function normaliser(Portfolio $portfolio, array $etats): array
    {
        $validations = $this->getValidations($portfolio);
        $normalises = [];

        foreach ($etats as $id => $etat) {
            if (!isset($validations[(int) $id])) {
                throw new \DomainException('Une ligne de la grille n\'appartient pas à ce portfolio. Rechargez la page.');
            }
            if (!is_numeric($etat) || !isset(self::NIVEAUX[(int) $etat])) {
                throw new \DomainException('Niveau d\'évaluation inconnu.');
            }
            $normalises[(int) $id] = (int) $etat;
        }

        return $normalises;
    }

    private function appliquer(array $validations, array $etats, ?Enseignant $enseignant): void
    {
        foreach ($etats as $id => $etat) {
            $validation = $validations[$id];
            if ((int) $validation->isEtat() === $etat) {
                continue;
            }

            $validation->setEtat($etat);
            $validation->setEnseignant($enseignant);
            if ($validation->getDateCreation() === null) {
                $validation->setDateCreation(new \DateTime());
            }
            $validation->setDateModification(new \DateTime());
        }
    }
}
