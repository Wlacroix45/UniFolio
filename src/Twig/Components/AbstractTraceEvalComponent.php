<?php

/*
 * Copyright (c) 2023. | Cyndel Herolt | IUT de Troyes  - All Rights Reserved
 * @author cyndelherolt
 * @project UniFolio
 */
namespace App\Twig\Components;

use App\Controller\BaseController;
use App\Entity\Commentaire;
use App\Entity\Trace;
use App\Event\CommentaireEvent;
use App\Event\EvaluationEvent;
use App\Repository\CommentaireRepository;
use App\Repository\TraceRepository;
use App\Repository\ValidationRepository;
use App\Security\Voter\DepartementVoter;
use App\Service\EvaluationPortfolio;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

class AbstractTraceEvalComponent extends BaseController
{
//todo: corriger le bug de form reponse (un pour chaque commentaire)
    use DefaultActionTrait;

    #[LiveProp]
    public ?int $id;

    private ?Trace $traceCache = null;
    private ?int $traceCacheId = null;

    #[LiveProp(writable: true)]
    public ?string $selectedValidation = '';

    private Commentaire $commentaire;

    #[LiveProp(writable: true)]
    public ?string $commentContent = '';

    #[LiveProp(writable: true)]
    public ?string $commentResponseContent = '';

    #[LiveProp(writable: true)]
    public ?string $commentVisibility = 'true';

    #[LiveProp(writable: true)]
    public ?int $commentaireReponseId = null;

    private EventDispatcherInterface $eventDispatcher;


    public function __construct(
        protected TraceRepository $traceRepository,
        private FormFactoryInterface $formFactory,
        private RequestStack $requestStack,
        protected ValidationRepository $validationRepository,
        protected CommentaireRepository $commentaireRepository,
        #[Required] public Security $security,
        EventDispatcherInterface $eventDispatcher,
        protected EvaluationPortfolio $evaluationPortfolio
    ) {
//        $this->Trace = $this->getTrace();

        // Créez une instance de votre entité Commentaire
        $this->commentaire = new Commentaire();
        $this->eventDispatcher = $eventDispatcher;
    }

    #[LiveAction]
    public function changeValidation(EventDispatcherInterface $eventDispatcher)
    {
        $trace = $this->getTrace();
        $this->denyAccessUnlessGranted(DepartementVoter::EVALUATE, $trace);

        $selectedValidation = $this->selectedValidation;
        list($validationId, $state) = explode('-', $selectedValidation);
        $validationId = intval($validationId);
        $state = intval($state);

        $validation = $this->validationRepository->find($validationId);
        if ($validation === null || $validation->getTrace()?->getId() !== $trace->getId()) {
            throw $this->createAccessDeniedException('Cette validation n\'appartient pas à la trace.');
        }
        if ($this->evaluationPortfolio->isTraceVerrouillee($trace)) {
            throw $this->createAccessDeniedException('L\'évaluation de ce portfolio est validée.');
        }
        if (!isset(EvaluationPortfolio::NIVEAUX[$state])) {
            return;
        }

        // Mettre à jour le validation dans la base de données
        $validation->setEtat($state);
        $validation->setEnseignant($this->security->getUser()->getEnseignant());
        $validation->setDateCreation(new \DateTime());
        if ($validation->getDateCreation() != null) {
            $validation->setDateModification(new \DateTime());
        }
        $this->validationRepository->save($validation, true);

        $event = new EvaluationEvent($validation);
        $eventDispatcher->dispatch($event, EvaluationEvent::EVALUATED);
    }


    #[LiveAction]
    public function handleComment()
    {
        $trace = $this->getTrace();
        $this->denyAccessUnlessGranted(DepartementVoter::EVALUATE, $trace);

        if (!$this->commentContent) {
            return;
        }

        if ($this->commentVisibility == 'true') {
            $this->commentVisibility = true;
        } else {
            $this->commentVisibility = false;
        }

        $this->commentaire->setContenu($this->commentContent);
        $this->commentaire->setVisibilite((bool) $this->commentVisibility);
        $this->commentaire->setEnseignant($this->security->getUser()->getEnseignant());
        $this->commentaire->setTrace($trace);
        $this->commentaire->setDatecreation(new \DateTime());

        $this->commentaireRepository->save($this->commentaire, true);

        if ($this->commentaire->isVisibilite() == 1) {
            $event = new CommentaireEvent($this->commentaire);
            $this->eventDispatcher->dispatch($event, CommentaireEvent::COMMENTED);
        }

        $this->commentContent = '';
        $this->commentVisibility = false;
        // Ajoutez cette ligne pour préparer une nouvelle entité Commentaire pour la prochaine utilisation
        $this->commentaire = new Commentaire();
    }

    #[LiveAction]
    public function handleResponseForm(#[LiveArg] int $commentaireId)
    {
        $this->commentaireReponseId = $commentaireId;
    }

    #[LiveAction]
    public function handleCommentResponse(#[LiveArg] int $commentParent)
    {
        $trace = $this->getTrace();
        $this->denyAccessUnlessGranted(DepartementVoter::EVALUATE, $trace);

        $commentParent = $this->commentaireRepository->find($commentParent);
        if ($commentParent === null || $commentParent->getTrace()?->getId() !== $trace->getId()) {
            throw $this->createAccessDeniedException('Ce commentaire n\'appartient pas à la trace.');
        }

        if ($this->commentVisibility == 'true') {
            $this->commentVisibility = true;
        } else {
            $this->commentVisibility = false;
        }

        $this->commentaire->setContenu($this->commentResponseContent);
        $this->commentaire->setVisibilite((bool) $this->commentVisibility);
        $this->commentaire->setEnseignant($this->security->getUser()->getEnseignant());
        $this->commentaire->setTrace($trace);
        $this->commentaire->setDatecreation(new \DateTime());
        $this->commentaire->setCommentaireParent($commentParent->getId());

        $this->commentaireRepository->save($this->commentaire, true);

//        if ($this->commentaire->isVisibilite() == 1) {
        $event = new CommentaireEvent($this->commentaire);
        $this->eventDispatcher->dispatch($event, CommentaireEvent::COMMENTED);
//        }

        $this->commentResponseContent = '';
        $this->commentVisibility = false;
        // Ajoutez cette ligne pour préparer une nouvelle entité Commentaire pour la prochaine utilisation
        $this->commentaire = new Commentaire();
    }

    #[LiveAction]
    public function removeComment(#[LiveArg] int $commentId)
    {
        $trace = $this->getTrace();
        $this->denyAccessUnlessGranted(DepartementVoter::EVALUATE, $trace);

        $comment = $this->commentaireRepository->find($commentId);
        if ($comment === null || $comment->getTrace()?->getId() !== $trace->getId()) {
            throw $this->createAccessDeniedException('Ce commentaire n\'appartient pas à la trace.');
        }
        $this->commentaireRepository->remove($comment, true);
        $commentairesEnfants = $this->commentaireRepository->findBy(['commentaire_parent' => $commentId]);
        if ($commentairesEnfants) {
            foreach ($commentairesEnfants as $commentaireEnfant) {
                $this->commentaireRepository->remove($commentaireEnfant, true);
            }
        }
    }
    #[PostMount]
    #[PostHydrate]
    public function controlerAcces(): void
    {
        $this->denyAccessUnlessGranted(DepartementVoter::VIEW, $this->getTrace());
    }

    public function isVerrouillee(): bool
    {
        return $this->evaluationPortfolio->isTraceVerrouillee($this->getTrace());
    }

    public function getTrace(): ?Trace
    {
        if ($this->id === null) {
            return null;
        }

        if ($this->traceCacheId === $this->id && $this->traceCache !== null) {
            return $this->traceCache;
        }

        $this->traceCache = $this->traceRepository->find($this->id);
        $this->traceCacheId = $this->id;

        return $this->traceCache;
    }
}
