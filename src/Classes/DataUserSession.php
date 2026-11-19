<?php

/*
 * Copyright (c) 2023. | Cyndel Herolt | IUT de Troyes  - All Rights Reserved
 * @author cyndelherolt
 * @project UniFolio
 */
namespace App\Classes;

use App\Entity\Annee;
use App\Entity\Departement;
use App\Entity\Diplome;
use App\Entity\Enseignant;
use App\Entity\EnseignantDepartement;
use App\Entity\Etudiant;
use App\Entity\Groupe;
use App\Entity\Semestre;
use App\Entity\Users;
use App\Enum\DepartementRoleEnum;
use App\Repository\AnneeRepository;
use App\Repository\AnneeUniversitaireRepository;
use App\Repository\DepartementRepository;
use App\Repository\DiplomeRepository;
use App\Repository\EnseignantDepartementRepository;
use App\Repository\EnseignantRepository;
use App\Repository\EtudiantRepository;
use App\Repository\GroupeRepository;
use App\Repository\NotificationRepository;
use App\Repository\SemestreRepository;
use App\Repository\TypeGroupeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

// Récupère les données d'une session utilisateur
class DataUserSession
{
    /**
     * @var Semestre[]
     */
    protected array $semestres = [];

    /**
     * @var Diplome[]
     */
    protected array $diplomes = [];

    /**
     * @var Annee[]
     */
    protected array $annees = [];

    protected ?Departement $departement = null;

    protected AnneeUniversitaireRepository $anneeUniversitaireRepository;

    private array $semestresActifs = [];

    private ?Etudiant $etudiant = null;

    protected ?Enseignant $enseignant = null;

    /**
     * @var Departement[]
     */
    protected array $allDepartements = [];

    protected array $groupes = [];

    protected array $typesGroupes = [];

    protected RequestStack $requestStack;

    /**
     * DataUserSession constructor.
     *
     * @throws NonUniqueResultException
     */
    public function __construct(
        protected SemestreRepository $semestreRepository,
        protected AnneeRepository $anneeRepository,
        protected DiplomeRepository $diplomeRepository,
        protected EnseignantRepository $enseignantRepository,
        protected EtudiantRepository $etudiantRepository,
        protected DepartementRepository $departementRepository,
        protected EnseignantDepartementRepository $enseignantDepartementRepository,
        protected GroupeRepository $groupeRepository,
        protected TypeGroupeRepository $typeGroupeRepository,
        protected TokenStorageInterface $user,
        protected Security $security,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher,
        RequestStack $session,
        protected NotificationRepository $notificationRepository,
    ) {
        $this->requestStack = $session;

        $currentUser = $this->getUser();
        if (!$currentUser instanceof UserInterface) {
            return;
        }

        $activeSession = $this->requestStack->getCurrentRequest()?->hasSession()
            ? $this->requestStack->getSession()
            : null;
        $deptSession = $activeSession?->get('departement');

        $username = $currentUser->getUserIdentifier();
        $enseignant = $this->enseignantRepository->findOneBy(['username' => $username]);
        $etudiant = $this->etudiantRepository->findOneBy(['username' => $username]);

        $this->enseignant = $enseignant;
        $this->etudiant = $etudiant;

        if ($enseignant !== null) {
            $this->allDepartements = $this->departementRepository->findDepartementEnseignant($enseignant);
            $deptId = $this->extractDepartementId($deptSession);

            if ($deptId !== null) {
                $candidate = $this->departementRepository->find($deptId);
                if (
                    $candidate !== null
                    && ($enseignant->getEnseignantDepartement($candidate) !== null
                        || in_array('ROLE_ADMIN', $currentUser->getRoles(), true))
                ) {
                    $this->departement = $candidate;
                }
            }

            if ($this->departement === null) {
                $defaultDepts = $this->departementRepository->findDepartementEnseignantDefaut($enseignant);
                if (!empty($defaultDepts)) {
                    $this->departement = $defaultDepts[0];
                } elseif (!empty($this->allDepartements)) {
                    $this->departement = $this->allDepartements[0];
                }
            }

            if ($this->departement !== null) {
                $this->loadDepartementData($this->departement);
            }
        } elseif ($etudiant !== null) {
            $deptId = $this->extractDepartementId($deptSession);
            if ($deptId !== null) {
                $this->departement = $this->departementRepository->find($deptId);
            }
            if ($this->departement === null) {
                $this->departement = $this->departementRepository->findDepartementEtudiant($etudiant);
            }
            $this->groupes = $this->groupeRepository->findGroupesEtudiant($etudiant);
            $this->typesGroupes = $this->typeGroupeRepository->findTypesGroupesEtudiant($etudiant);

            if ($etudiant->getUsername() === 'etudiant' && $this->departement !== null) {
                $this->loadDepartementData($this->departement);
            }
        } elseif (in_array('ROLE_ADMIN', $currentUser->getRoles(), true)) {
            $this->allDepartements = $this->departementRepository->findAll();
            $deptId = $this->extractDepartementId($deptSession);

            if ($deptId !== null) {
                $this->departement = $this->departementRepository->find($deptId);
            }
            if ($this->departement === null && !empty($this->allDepartements)) {
                $this->departement = $this->allDepartements[0];
            }
            if ($this->departement !== null) {
                $this->loadDepartementData($this->departement);
            }
        } else {
            throw new AccessDeniedException('Vous n\'avez pas accès à cette page');
        }
    }

    private function extractDepartementId(mixed $deptSession): ?int
    {
        if (is_array($deptSession) && isset($deptSession[0])) {
            return $deptSession[0] instanceof Departement
                ? $deptSession[0]->getId()
                : (is_numeric($deptSession[0]) ? (int) $deptSession[0] : null);
        }

        if ($deptSession instanceof Departement) {
            return $deptSession->getId();
        }

        if (is_numeric($deptSession)) {
            return (int) $deptSession;
        }

        return null;
    }

    private function loadDepartementData(Departement $departement): void
    {
        $this->semestres = $this->semestreRepository->findByDepartement($departement);
        $this->semestresActifs = [];
        foreach ($this->semestres as $semestre) {
            if ($semestre->isActif()) {
                $this->semestresActifs[] = $semestre;
            }
        }
        $this->diplomes = $this->diplomeRepository->findByDepartement($departement);
        $this->annees = $this->anneeRepository->findByDepartement($departement);
        $this->typesGroupes = $this->typeGroupeRepository->findByDepartementSemestresActifs($departement);
    }

    public function getUser(): ?UserInterface
    {
        if (null !== $this->user->getToken()) {
            return $this->user->getToken()->getUser();
        }

        return null;
    }

    public function getDepartement(): ?Departement
    {
        return $this->departement;
    }

    public function setDepartement(Departement $departement): void
    {
        $this->departement = $departement;
    }

    public function getAllDepartementsEnseignant(): array
    {
        return $this->allDepartements;
    }

    /**
     * @return EnseignantDepartement[]
     */
    public function getEnseignantDepartements(): array
    {
        if ($this->enseignant === null) {
            return [];
        }

        return $this->enseignantDepartementRepository->findByEnseignant($this->enseignant);
    }

    public function getCurrentRole(): ?DepartementRoleEnum
    {
        if ($this->enseignant === null || $this->departement === null) {
            return null;
        }

        return $this->enseignant->getRoleForDepartement($this->departement);
    }

    public function getCurrentRoleLibelle(): ?string
    {
        $user = $this->getUser();
        if ($user !== null && in_array('ROLE_ADMIN', $user->getRoles(), true) && $this->enseignant === null) {
            return 'Administrateur';
        }

        return $this->getCurrentRole()?->getLibelle();
    }

    public function isEditeur(): bool
    {
        $user = $this->getUser();
        if ($user instanceof Users && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        return $this->getCurrentRole() === DepartementRoleEnum::EDITEUR;
    }

    public function isEvaluateur(): bool
    {
        $user = $this->getUser();
        if ($user instanceof Users && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        $role = $this->getCurrentRole();

        return $role === DepartementRoleEnum::EVALUATEUR || $role === DepartementRoleEnum::EDITEUR;
    }

    public function getEnseignant(): ?Enseignant
    {
        return $this->enseignant;
    }

    public function getSemestres(): array
    {
        return $this->semestres;
    }

    public function getSemestresActifs(): array
    {
        return $this->semestresActifs;
    }

    public function getSemestresByAnnee(Annee $annee, int $ordre): array
    {
        $semestres = [];
        foreach ($this->semestres as $semestre) {
            if ($semestre->getAnnee() === $annee && $annee->getOrdre() === $ordre) {
                $semestres[] = $semestre;
            }
        }

        return $semestres;
    }

    public function getDiplomes(): array
    {
        return $this->diplomes;
    }

    public function getAnnees(): array
    {
        return $this->annees;
    }

    public function getAllEnseignants(): array
    {
        if ($this->departement === null) {
            return [];
        }

        return $this->enseignantRepository->findByDepartement($this->departement);
    }

    public function getEtudiantsDept(): array
    {
        if ($this->departement === null) {
            return [];
        }

        return $this->etudiantRepository->findByDepartementArray($this->departement);
    }

    public function getEtudiantsSemestre(): array
    {
        $etudiants = [];
        foreach ($this->semestresActifs as $semestre) {
            $etudiants[] = $this->etudiantRepository->findBySemestre($semestre);
        }

        return $etudiants;
    }

    public function getEtudiantAnnee(): array
    {
        $etudiants = [];
        foreach ($this->annees as $annee) {
            $etudiants[] = $this->etudiantRepository->findByAnnee($annee);
        }

        return $etudiants;
    }

    public function getTypesGroupe(): array
    {
        return $this->typesGroupes;
    }

    public function getGroupes(): array
    {
        if ($this->departement === null) {
            return [];
        }

        return $this->groupeRepository->findByDepartementSemestreActif($this->departement);
    }

    public function DepartementDefaut(): array
    {
        if ($this->enseignant === null) {
            return [];
        }

        return $this->departementRepository->findDepartementEnseignantDefaut($this->enseignant);
    }

    public function getNotifications(): array
    {
        $user = $this->getUser();
        if (!$user instanceof Users) {
            return [];
        }

        if ($this->security->isGranted('ROLE_ENSEIGNANT') && $user->getEnseignant() !== null) {
            return $this->notificationRepository->findBy(
                ['enseignant' => $user->getEnseignant()],
                ['dateCreation' => 'DESC']
            );
        }

        if ($this->security->isGranted('ROLE_ETUDIANT') && $user->getEtudiant() !== null) {
            return $this->notificationRepository->findBy(
                ['etudiant' => $user->getEtudiant()],
                ['dateCreation' => 'DESC']
            );
        }

        return [];
    }

    public function getEtudiant(): ?Etudiant
    {
        return $this->etudiant;
    }
}
