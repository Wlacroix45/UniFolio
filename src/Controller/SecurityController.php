<?php

/*
 * Copyright (c) 2023. | Cyndel Herolt | IUT de Troyes  - All Rights Reserved
 * @author cyndelherolt
 * @project UniFolio
 */
namespace App\Controller;

use App\Classes\DataUserSession;
use App\Entity\Enseignant;
use App\Repository\EnseignantDepartementRepository;
use App\Repository\EnseignantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/enseignant')]
class SecurityController extends AbstractController
{
    public function __construct(
        protected DataUserSession $dataUserSession,
    ) {
    }

    #[Route('/security', name: 'app_security')]
    public function index(): Response
    {
        return $this->render('security/choix-departement.html.twig', [
            'controller_name' => 'SecurityController',
        ]);
    }

    #[Route(path: '/choix-departement', name: 'app_choix_departement')]
    public function choixDepartement(
        Request $request,
        EnseignantDepartementRepository $enseignantDepartementRepository,
        EnseignantRepository $enseignantRepository,
        RequestStack $session,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isGranted('ROLE_ENSEIGNANT')) {
            return $this->render('security/accessDenied.html.twig');
        }

        $user = $this->getUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        $enseignant = $enseignantRepository->findOneBy(['username' => $user->getUserIdentifier()]);
        if (!$enseignant) {
            return $this->redirectToRoute('app_login', [
                'message' => 'Vous devez être intervenant ou enseignant pour accéder à cette page.',
            ]);
        }

        $departements = $enseignantDepartementRepository->findByEnseignant($enseignant);

        if ('POST' === $request->getMethod()) {
            $selectedDeptId = $request->request->get('departement');
            $selected = $enseignantDepartementRepository->findOneBy([
                'enseignant' => $enseignant,
                'departement' => $selectedDeptId,
            ]);

            if ($selected === null || $selected->getDepartement() === null) {
                $this->addFlash('danger', 'Vous n\'avez pas accès à ce département.');

                return $this->redirectToRoute('app_choix_departement');
            }

            foreach ($departements as $ed) {
                $ed->setDefaut($ed->getId() === $selected->getId());
            }
            $entityManager->flush();

            $targetDepartement = $selected->getDepartement();
            $session->getSession()->set('departement', $targetDepartement->getId());
            $this->dataUserSession->setDepartement($targetDepartement);

            $this->addFlash('success', sprintf(
                'Département actif : %s (%s)',
                $targetDepartement->getLibelle(),
                $selected->getRole()->getLibelle()
            ));

            return $this->redirectToRoute('enseignant_dashboard');
        }

        return $this->render('security/choix-departement.html.twig', [
            'departements' => $departements,
            'user' => $user,
        ]);
    }
}
