<?php

namespace App\Controller;

use App\Repository\PortfolioRepository;
use App\Security\Voter\DepartementVoter;
use App\Service\EvaluationPortfolio;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/enseignant')]
class EvaluationController extends BaseController
{
    #[Route(
        '/portfolio/{id}/evaluation',
        name: 'app_evaluation_portfolio',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function show(
        int $id,
        PortfolioRepository $portfolioRepository,
        EvaluationPortfolio $evaluation
    ): Response {
        $portfolio = $portfolioRepository->find($id);
        if ($portfolio === null || !$portfolio->isVisibilite()) {
            throw $this->createNotFoundException('Portfolio introuvable.');
        }
        $this->denyAccessUnlessGranted(DepartementVoter::VIEW, $portfolio);

        return $this->render('evaluation/show.html.twig', [
            'portfolio' => $portfolio,
            'parties' => $evaluation->getParties($portfolio),
            'data_user' => $this->dataUserSession,
        ]);
    }
}
