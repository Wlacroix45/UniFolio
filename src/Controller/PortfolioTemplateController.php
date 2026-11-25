<?php

namespace App\Controller;

use App\Entity\Departement;
use App\Entity\PortfolioTemplate;
use App\Entity\TemplateSection;
use App\Entity\Users;
use App\Enum\PortfolioTemplateStatusEnum;
use App\Form\PortfolioTemplateType;
use App\Repository\PortfolioTemplateRepository;
use App\Security\Voter\DepartementVoter;
use App\Service\PortfolioTemplateSnapshotService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/enseignant/modeles-portfolio')]
class PortfolioTemplateController extends BaseController
{
    #[Route('', name: 'app_portfolio_template_index', methods: ['GET'])]
    public function index(
        Request $request,
        PortfolioTemplateRepository $portfolioTemplateRepository,
    ): Response {
        $departement = $this->dataUserSession->getDepartement();
        if ($departement === null) {
            if ($this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('warning', 'Aucun département actif trouvé pour afficher les modèles.');

                return $this->render('portfolio_template/index.html.twig', [
                    'templates' => [],
                    'departement' => null,
                    'currentStatut' => null,
                    'statuts' => PortfolioTemplateStatusEnum::cases(),
                    'canEdit' => false,
                    'data_user' => $this->dataUserSession,
                ]);
            }

            return $this->render('security/accessDenied.html.twig');
        }

        $this->denyAccessUnlessGranted(DepartementVoter::VIEW, $departement);

        $statutParam = $request->query->get('statut');
        $currentStatut = is_string($statutParam) && $statutParam !== ''
            ? PortfolioTemplateStatusEnum::tryFrom($statutParam)
            : null;

        $templates = $portfolioTemplateRepository->findByDepartement($departement, $currentStatut);

        return $this->render('portfolio_template/index.html.twig', [
            'templates' => $templates,
            'departement' => $departement,
            'currentStatut' => $currentStatut,
            'statuts' => PortfolioTemplateStatusEnum::cases(),
            'canEdit' => $this->isGranted(DepartementVoter::EDIT, $departement),
            'data_user' => $this->dataUserSession,
        ]);
    }

    #[Route('/nouveau', name: 'app_portfolio_template_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $departement = $this->requireActiveDepartement();
        $this->denyAccessUnlessGranted(DepartementVoter::EDIT, $departement);

        $template = new PortfolioTemplate();
        $template->setDepartement($departement);

        $user = $this->getUser();
        if ($user instanceof Users && $user->getEnseignant() !== null) {
            $template->setEnseignant($user->getEnseignant());
        }

        if ($request->isMethod('GET')) {
            $defaultSection = new TemplateSection();
            $defaultSection->setOrdre(1);
            $template->addSection($defaultSection);
        }

        $form = $this->createForm(PortfolioTemplateType::class, $template, [
            'departement' => $departement,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->normalizeSectionOrders($template);
            $template->touch();

            $entityManager->persist($template);
            $entityManager->flush();

            $this->addFlash('success', 'Le modèle de portfolio a été créé en brouillon.');

            return $this->redirectToRoute('app_portfolio_template_show', ['id' => $template->getId()]);
        }

        return $this->render('portfolio_template/form.html.twig', [
            'form' => $form,
            'template' => $template,
            'departement' => $departement,
            'isEdit' => false,
            'data_user' => $this->dataUserSession,
        ]);
    }

    #[Route('/{id}', name: 'app_portfolio_template_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(PortfolioTemplate $template): Response
    {
        $this->denyAccessUnlessGranted(DepartementVoter::VIEW, $template);

        return $this->render('portfolio_template/show.html.twig', [
            'template' => $template,
            'canEdit' => $this->isGranted(DepartementVoter::EDIT, $template),
            'data_user' => $this->dataUserSession,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_portfolio_template_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        PortfolioTemplate $template,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted(DepartementVoter::EDIT, $template);

        if ($template->isArchived()) {
            $this->addFlash('warning', 'Un modèle archivé ne peut plus être modifié. Dupliquez-le pour créer un nouveau brouillon.');

            return $this->redirectToRoute('app_portfolio_template_show', ['id' => $template->getId()]);
        }

        $departement = $template->getDepartement();
        $form = $this->createForm(PortfolioTemplateType::class, $template, [
            'departement' => $departement,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($template->isPublished() && $template->getSections()->isEmpty()) {
                $this->addFlash('danger', 'Un modèle publié doit conserver au moins une section.');

                return $this->render('portfolio_template/form.html.twig', [
                    'form' => $form,
                    'template' => $template,
                    'departement' => $departement,
                    'isEdit' => true,
                    'data_user' => $this->dataUserSession,
                ]);
            }

            $this->normalizeSectionOrders($template);
            $template->touch();

            $entityManager->flush();

            $this->addFlash('success', 'Le modèle de portfolio a été mis à jour.');

            return $this->redirectToRoute('app_portfolio_template_show', ['id' => $template->getId()]);
        }

        return $this->render('portfolio_template/form.html.twig', [
            'form' => $form,
            'template' => $template,
            'departement' => $departement,
            'isEdit' => true,
            'data_user' => $this->dataUserSession,
        ]);
    }

    #[Route('/{id}/publier', name: 'app_portfolio_template_publish', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function publish(
        Request $request,
        PortfolioTemplate $template,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted(DepartementVoter::EDIT, $template);

        if (!$this->isCsrfTokenValid('publish_template_' . $template->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_portfolio_template_show', ['id' => $template->getId()]);
        }

        try {
            $template->publish();
            $entityManager->flush();
            $this->addFlash('success', 'Le modèle a été publié et est désormais disponible pour les étudiants.');
        } catch (\LogicException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_portfolio_template_show', ['id' => $template->getId()]);
    }

    #[Route('/{id}/archiver', name: 'app_portfolio_template_archive', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function archive(
        Request $request,
        PortfolioTemplate $template,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted(DepartementVoter::EDIT, $template);

        if (!$this->isCsrfTokenValid('archive_template_' . $template->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_portfolio_template_show', ['id' => $template->getId()]);
        }

        try {
            $template->archive();
            $entityManager->flush();
            $this->addFlash('success', 'Le modèle a été archivé. Les portfolios déjà démarrés restent intacts.');
        } catch (\LogicException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_portfolio_template_show', ['id' => $template->getId()]);
    }

    #[Route('/{id}/dupliquer', name: 'app_portfolio_template_duplicate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function duplicate(
        Request $request,
        PortfolioTemplate $template,
        PortfolioTemplateSnapshotService $snapshotService,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted(DepartementVoter::EDIT, $template);

        if (!$this->isCsrfTokenValid('duplicate_template_' . $template->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_portfolio_template_show', ['id' => $template->getId()]);
        }

        $user = $this->getUser();
        $enseignant = $user instanceof Users ? $user->getEnseignant() : null;

        $copy = $snapshotService->duplicateAsDraft($template, $enseignant);
        $entityManager->persist($copy);
        $entityManager->flush();

        $this->addFlash('success', 'Une copie en brouillon du modèle a été créée.');

        return $this->redirectToRoute('app_portfolio_template_edit', ['id' => $copy->getId()]);
    }

    private function requireActiveDepartement(): Departement
    {
        $departement = $this->dataUserSession->getDepartement();
        if ($departement === null) {
            throw $this->createAccessDeniedException('Aucun département actif n\'est associé à votre session.');
        }

        return $departement;
    }

    private function normalizeSectionOrders(PortfolioTemplate $template): void
    {
        $sections = $template->getOrderedSections();
        $position = 1;
        foreach ($sections as $section) {
            $section->setOrdre($position);
            ++$position;
        }
    }
}
