<?php

namespace App\Twig\Components;

use App\Controller\BaseController;
use App\Entity\Commentaire;
use App\Entity\Portfolio;
use App\Enum\EvaluationStatutEnum;
use App\Event\CommentaireEvent;
use App\Repository\PortfolioRepository;
use App\Security\Voter\DepartementVoter;
use App\Service\EvaluationPortfolio;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent('PortfolioEvaluationComponent')]
final class PortfolioEvaluationComponent extends BaseController
{
    use DefaultActionTrait;

    #[LiveProp]
    public int $portfolioId = 0;

    /** @var array<string, int|string> */
    #[LiveProp(writable: true)]
    public array $etats = [];

    #[LiveProp(writable: true)]
    public string $appreciation = '';

    #[LiveProp(writable: true)]
    public bool $appreciationVisible = false;

    public ?string $succes = null;
    public ?string $erreur = null;

    private ?Portfolio $portfolio = null;

    public function __construct(
        private readonly PortfolioRepository $portfolioRepository,
        private readonly EvaluationPortfolio $evaluation,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function mount(int $portfolioId): void
    {
        $this->portfolioId = $portfolioId;
    }

    #[PostMount]
    public function initialiser(): void
    {
        $this->controlerAcces();
        $this->chargerEtats();
    }

    #[PostHydrate]
    public function controlerAcces(): void
    {
        $this->denyAccessUnlessGranted(DepartementVoter::VIEW, $this->getPortfolio());
    }

    #[LiveAction]
    public function enregistrer(): void
    {
        $this->executer(
            fn (Portfolio $p) => $this->evaluation->enregistrer($p, $this->getEtatsParId(), $this->getAuteur()),
            'Brouillon enregistré.'
        );
    }

    #[LiveAction]
    public function valider(): void
    {
        $this->executer(
            fn (Portfolio $p) => $this->evaluation->valider($p, $this->getEtatsParId(), $this->getAuteur()),
            'Évaluation validée et verrouillée.'
        );
    }

    public function getPortfolio(): Portfolio
    {
        $this->portfolio ??= $this->portfolioRepository->find($this->portfolioId);
        if ($this->portfolio === null || !$this->portfolio->isVisibilite()) {
            throw $this->createNotFoundException('Portfolio introuvable.');
        }

        return $this->portfolio;
    }

    public function getParties(): array
    {
        return $this->evaluation->getParties($this->getPortfolio());
    }

    /** @return array<int, string> */
    public function getNiveaux(): array
    {
        return EvaluationPortfolio::NIVEAUX;
    }

    public function getStatut(): EvaluationStatutEnum
    {
        $portfolio = $this->getPortfolio();
        if ($portfolio->isEvaluationValidee()) {
            return EvaluationStatutEnum::EVALUE;
        }

        return $portfolio->getDateBrouillonEvaluation() !== null || $this->getNombreEvaluees() > 0
            ? EvaluationStatutEnum::EN_COURS
            : EvaluationStatutEnum::A_EVALUER;
    }

    public function getNombreCompetences(): int
    {
        return count($this->evaluation->getValidations($this->getPortfolio()));
    }

    public function getNombreEvaluees(): int
    {
        return count(array_filter(
            $this->evaluation->getValidations($this->getPortfolio()),
            static fn ($validation) => (int) $validation->isEtat() !== 0
        ));
    }

    public function isPeutEvaluer(): bool
    {
        return $this->isGranted(DepartementVoter::EVALUATE, $this->getPortfolio());
    }

    public function isModifiable(): bool
    {
        return $this->isPeutEvaluer() && !$this->getPortfolio()->isEvaluationValidee();
    }

    private function executer(callable $action, string $succes): void
    {
        $portfolio = $this->getPortfolio();
        $this->denyAccessUnlessGranted(DepartementVoter::EVALUATE, $portfolio);

        try {
            $action($portfolio);
        } catch (\DomainException $e) {
            $this->erreur = $e->getMessage();

            return;
        }

        $this->publierAppreciation($portfolio);
        $this->chargerEtats();
        $this->succes = $succes;
    }

    private function publierAppreciation(Portfolio $portfolio): void
    {
        $contenu = trim($this->appreciation);
        if ($contenu === '') {
            return;
        }

        $commentaire = (new Commentaire())
            ->setContenu($contenu)
            ->setVisibilite($this->appreciationVisible)
            ->setEnseignant($this->getAuteur())
            ->setPortfolio($portfolio)
            ->setDateCreation(new \DateTime())
            ->setDateModification(new \DateTime());
        $portfolio->addCommentaire($commentaire);
        $this->entityManager->persist($commentaire);
        $this->entityManager->flush();

        if ($this->appreciationVisible) {
            $this->eventDispatcher->dispatch(new CommentaireEvent($commentaire), CommentaireEvent::COMMENTED);
        }

        $this->appreciation = '';
        $this->appreciationVisible = false;
    }

    private function getAuteur(): ?\App\Entity\Enseignant
    {
        return $this->dataUserSession->getEnseignant();
    }

    /** @return array<int, int|string> */
    private function getEtatsParId(): array
    {
        $etats = [];
        foreach ($this->etats as $cle => $etat) {
            $etats[(int) substr((string) $cle, 1)] = $etat;
        }

        return $etats;
    }

    private function chargerEtats(): void
    {
        $this->etats = [];
        foreach ($this->evaluation->getValidations($this->getPortfolio()) as $id => $validation) {
            $this->etats['v' . $id] = (string) (int) $validation->isEtat();
        }
    }
}
