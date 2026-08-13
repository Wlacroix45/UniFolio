<?php

/*
 * Copyright (c) 2023. | Cyndel Herolt | IUT de Troyes  - All Rights Reserved
 * @author cyndelherolt
 * @project UniFolio
 */
namespace App\Controller\Admin;

use App\Entity\Annee;
use App\Entity\AnneeUniversitaire;
use App\Entity\ApcApprentissageCritique;
use App\Entity\ApcNiveau;
use App\Entity\ApcParcours;
use App\Entity\ApcReferentiel;
use App\Entity\Commentaire;
use App\Entity\Competence;
use App\Entity\Departement;
use App\Entity\Diplome;
use App\Entity\Enseignant;
use App\Entity\Etudiant;
use App\Entity\Groupe;
use App\Entity\Page;
use App\Entity\Portfolio;
use App\Entity\Semestre;
use App\Entity\Templates;
use App\Entity\Trace;
use App\Entity\TypeGroupe;
use App\Entity\Users;
use App\Entity\Validation;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[AdminDashboard(routePath:'/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        $site = $_ENV['SITE'];
        return $this->render('Admin/dashboard.html.twig', [
            'site' => $site,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('UniFolio')
            ->setTranslationDomain('admin');
    }

    public function configureMenuItems(): iterable
    {
        // récupérer la variable SITE de .env
        if ($_ENV['SITE'] === 'IUTTroyes') {
            yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');
            yield MenuItem::linkToUrl('Site étudiant', 'fas fa-user', '/dashboard?_switch_user=etudiant');
            yield MenuItem::linkToUrl('Site enseignant', 'fas fa-user', '/dashboard?_switch_user=enseignant');
            yield MenuItem::linkToLogout('Déconnexion', 'fa fa-arrow-right-from-bracket');
        }
        yield MenuItem::section('Structure');
        yield MenuItem::linkTo(DepartementCrudController::class, 'Gestion des départements', 'fas fa-list');
        yield MenuItem::linkTo(DiplomeCrudController::class, 'Gestion des diplomes', 'fas fa-list');
        yield MenuItem::linkTo(AnneeCrudController::class, 'Gestion des années', 'fas fa-list');
        yield MenuItem::linkTo(SemestreCrudController::class, 'Gestion des semestres', 'fas fa-list');
        yield MenuItem::linkTo(TypeGroupeCrudController::class, 'Gestion des types de groupes', 'fas fa-list');
        yield MenuItem::linkTo(GroupeCrudController::class, 'Gestion des groupes', 'fas fa-list');

        yield MenuItem::section('Apc');
        yield MenuItem::linkTo(ApcReferentielCrudController::class, 'Gestion des référentiels', 'fas fa-list');
        yield MenuItem::linkTo(ApcParcoursCrudController::class, 'Gestion des parcours', 'fas fa-list');
        yield MenuItem::linkTo(CompetenceCrudController::class, 'Gestion des compétences', 'fas fa-list');
        yield MenuItem::linkTo(ApcNiveauCrudController::class, 'Gestion des niveaux', 'fas fa-list');
        yield MenuItem::linkTo(ApcApprentissageCritiqueCrudController::class, 'Gestion des apprentissages critiques', 'fas fa-list');


        yield MenuItem::section('Utilisateurs');
        yield MenuItem::linkTo(EtudiantCrudController::class, 'Gestion des etudiants', 'fas fa-list');
        yield MenuItem::linkTo(EnseignantCrudController::class, 'Gestion des enseignants', 'fas fa-list');

        yield MenuItem::section('Portfolios');
        yield MenuItem::linkTo(TraceCrudController::class, 'Gestion des traces', 'fas fa-list');
        yield MenuItem::linkTo(PageCrudController::class, 'Gestion des pages', 'fas fa-list');
        yield MenuItem::linkTo(PortfolioCrudController::class, 'Gestion des portfolios', 'fas fa-list');
        yield MenuItem::linkTo(CommentaireCrudController::class, 'Gestion des commentaires', 'fas fa-list');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        return parent::configureUserMenu($user)
            ->addMenuItems([
//                MenuItem::LinkToRoute('Mon profil', 'fas fa-user', 'app_profil'),
            ]);
    }

    // défini des paramètres pour l'ensemble des CRUD controller
    public function configureCrud(): Crud
    {
        return Crud::new()
            ->setPaginatorPageSize(30)
            ;
    }
}
