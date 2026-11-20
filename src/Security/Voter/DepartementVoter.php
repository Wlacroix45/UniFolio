<?php

namespace App\Security\Voter;

use App\Classes\DataUserSession;
use App\Entity\Annee;
use App\Entity\ApcReferentiel;
use App\Entity\Departement;
use App\Entity\Diplome;
use App\Entity\Etudiant;
use App\Entity\Portfolio;
use App\Entity\Semestre;
use App\Entity\Trace;
use App\Entity\Users;
use App\Enum\DepartementRoleEnum;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Cloisonne les droits d'accès (affichage, évaluation, édition) par département
 * selon le rôle de l'utilisateur connecté dans le département concerné.
 *
 * @extends Voter<string, mixed>
 */
class DepartementVoter extends Voter
{
    public const VIEW = 'DEPARTEMENT_VIEW';
    public const EVALUATE = 'DEPARTEMENT_EVALUATE';
    public const EDIT = 'DEPARTEMENT_EDIT';

    public function __construct(
        private readonly DataUserSession $dataUserSession,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EVALUATE, self::EDIT], true)) {
            return false;
        }

        return $subject === null
            || $subject instanceof Departement
            || $subject instanceof Portfolio
            || $subject instanceof Trace
            || $subject instanceof Etudiant
            || $subject instanceof Diplome
            || $subject instanceof Annee
            || $subject instanceof Semestre
            || $subject instanceof ApcReferentiel;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof Users) {
            return false;
        }

        // L'administrateur a tous les droits sur l'ensemble des départements
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Un étudiant peut toujours consulter son propre portfolio / sa propre trace (même sans département)
        if ($attribute === self::VIEW && $user->getEtudiant() !== null) {
            if ($subject instanceof Portfolio) {
                return $this->isSameEtudiant($subject->getEtudiant(), $user->getEtudiant());
            }
            if ($subject instanceof Trace) {
                return $this->isSameEtudiant($subject->getBibliotheque()?->getEtudiant(), $user->getEtudiant());
            }
        }

        $departement = $this->resolveDepartement($subject);
        if ($departement === null) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($user, $departement),
            self::EVALUATE => $user->hasRoleInDepartement($departement, DepartementRoleEnum::EVALUATEUR),
            self::EDIT => $user->hasRoleInDepartement($departement, DepartementRoleEnum::EDITEUR),
            default => false,
        };
    }

    private function canView(Users $user, Departement $departement): bool
    {
        if ($user->getEnseignant() !== null) {
            return $user->getRoleForDepartement($departement) !== null;
        }

        if ($user->getEtudiant() !== null) {
            foreach ($user->getDepartements() as $userDept) {
                if ($this->isSameDepartement($userDept, $departement)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function resolveDepartement(mixed $subject): ?Departement
    {
        if ($subject instanceof Departement) {
            return $subject;
        }

        if ($subject instanceof Portfolio) {
            return $subject->getEtudiant()?->getSemestre()?->getAnnee()?->getDiplome()?->getDepartement()
                ?? $subject->getAnnee()?->getDiplome()?->getDepartement();
        }

        if ($subject instanceof Trace) {
            return $subject->getBibliotheque()?->getEtudiant()?->getSemestre()?->getAnnee()?->getDiplome()?->getDepartement();
        }

        if ($subject instanceof Etudiant) {
            return $subject->getSemestre()?->getAnnee()?->getDiplome()?->getDepartement();
        }

        if ($subject instanceof Diplome) {
            return $subject->getDepartement();
        }

        if ($subject instanceof Annee) {
            return $subject->getDiplome()?->getDepartement();
        }

        if ($subject instanceof Semestre) {
            return $subject->getAnnee()?->getDiplome()?->getDepartement();
        }

        if ($subject instanceof ApcReferentiel) {
            return $subject->getDepartement();
        }

        if ($subject === null) {
            return $this->dataUserSession->getDepartement();
        }

        return null;
    }

    private function isSameDepartement(?Departement $a, ?Departement $b): bool
    {
        if ($a === null || $b === null) {
            return false;
        }

        if ($a === $b) {
            return true;
        }

        return $a->getId() !== null && $a->getId() === $b->getId();
    }

    private function isSameEtudiant(?Etudiant $a, ?Etudiant $b): bool
    {
        if ($a === null || $b === null) {
            return false;
        }

        if ($a === $b) {
            return true;
        }

        return $a->getId() !== null && $a->getId() === $b->getId();
    }
}
