<?php

/*
 * Copyright (c) 2023. | Cyndel Herolt | IUT de Troyes  - All Rights Reserved
 * @author cyndelherolt
 * @project UniFolio
 */
namespace App\Controller;

use App\Entity\Users;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    private readonly RequestStack $session;

    public function __construct(RequestStack $session)
    {
        $this->session = $session;
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ETUDIANT') && $user instanceof Users && $user->getEtudiant() !== null) {
            if ($this->session->getSession()->get('departement')) {
                return $this->redirectToRoute('etudiant_dashboard');
            }

            return $this->redirectToRoute('old_etudiant_dashboard');
        }

        if ($this->isGranted('ROLE_ENSEIGNANT') && $user instanceof Users && $user->getEnseignant() !== null) {
            return $this->redirectToRoute('enseignant_dashboard');
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin');
        }

        return $this->redirectToRoute('app_login');
    }
}
