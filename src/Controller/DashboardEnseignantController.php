<?php

/*
 * Copyright (c) 2023. | Cyndel Herolt | IUT de Troyes  - All Rights Reserved
 * @author cyndelherolt
 * @project UniFolio
 */
namespace App\Controller;

use App\Security\Voter\DepartementVoter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/enseignant')]
class DashboardEnseignantController extends BaseController
{
    #[Route('/dashboard', name: 'enseignant_dashboard')]
    public function index(): Response
    {
        $departement = $this->dataUserSession->getDepartement();

        if (
            !$this->isGranted('ROLE_ENSEIGNANT')
            || ($departement !== null && !$this->isGranted(DepartementVoter::VIEW, $departement))
        ) {
            return $this->render('security/accessDenied.html.twig');
        }

        return $this->render('dashboard_enseignant/index.html.twig', [
            'admin' => $this->getUser()->getUserIdentifier() === 'enseignant' ? '/admin?_switch_user=_exit' : '',
            'data_user' => $this->dataUserSession,
        ]);
    }
}
