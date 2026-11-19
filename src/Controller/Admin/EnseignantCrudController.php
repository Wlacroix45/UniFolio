<?php

namespace App\Controller\Admin;

use App\Entity\Enseignant;
use App\Entity\Users;
use App\Enum\DepartementRoleEnum;
use App\Form\EnseignantDepartementType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class EnseignantCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly RequestStack $requestStack,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Enseignant::class;
    }

    public function createEntity(string $entityFqcn): object
    {
        $enseignant = new Enseignant();
        $user = new Users();
        $user->setIsVerified(true);
        $user->setRoles(['ROLE_EVALUATEUR']);
        $enseignant->setUsers($user);

        return $enseignant;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addFieldset('Informations Enseignant')->setIcon('fa fa-user');
        yield TextField::new('prenom', 'Prénom')
            ->setFormTypeOption('required', true);
        yield TextField::new('nom', 'Nom')
            ->setFormTypeOption('required', true);
        yield TextField::new('mail_perso', 'Mail personnel')
            ->setFormType(EmailType::class);
        yield TextField::new('mail_univ', 'Mail universitaire')
            ->setFormTypeOption('required', true)
            ->setFormType(EmailType::class);
        yield TextField::new('telephone', 'Téléphone');
        yield TextField::new('username', 'Nom d\'utilisateur')
            ->setFormTypeOption('required', true);

        if ($pageName === Crud::PAGE_NEW) {
            yield TextField::new('password', 'Mot de passe')
                ->setFormTypeOption('mapped', false)
                ->setFormTypeOption('required', true);
        } else {
            yield TextField::new('password', 'Nouveau mot de passe')
                ->setFormTypeOption('mapped', false)
                ->setFormTypeOption('required', false)
                ->setHelp('Laissez vide pour conserver le mot de passe actuel.')
                ->onlyOnForms();
        }

        yield FormField::addFieldset('Rôles et Départements')->setIcon('fa fa-building-user');

        yield ChoiceField::new('roles', 'Rôles globaux')
            ->setChoices([
                'Évaluateur' => 'ROLE_EVALUATEUR',
                'Éditeur' => 'ROLE_EDITEUR',
                'Administrateur' => 'ROLE_ADMIN',
            ])
            ->allowMultipleChoices()
            ->setRequired(false)
            ->setHelp('Rôles Symfony globaux sur le compte utilisateur (ex. Administrateur).');

        yield CollectionField::new('enseignantDepartements', 'Départements et rôles associés')
            ->setEntryType(EnseignantDepartementType::class)
            ->setEntryIsComplex(true)
            ->allowAdd()
            ->allowDelete()
            ->setFormTypeOption('by_reference', false)
            ->setHelp(
                'Attribuez les départements et le rôle spécifique pour chacun '
                . '(ex. Éditeur en INFO, Évaluateur en GEA).'
            );
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Enseignant.e')
            ->setEntityLabelInPlural('Enseignant.es')
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(20)
            ->setPageTitle('index', 'Liste des enseignant.es')
            ->setPageTitle('new', 'Ajouter un.e enseignant.e')
            ->setPageTitle('edit', 'Modifier un.e enseignant.e');
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Enseignant) {
            parent::persistEntity($entityManager, $entityInstance);

            return;
        }

        $this->syncUserAndDepartements($entityInstance);

        $entityManager->persist($entityInstance->getUsers());
        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Enseignant) {
            parent::updateEntity($entityManager, $entityInstance);

            return;
        }

        $this->syncUserAndDepartements($entityInstance);

        $entityManager->persist($entityInstance->getUsers());
        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    private function syncUserAndDepartements(Enseignant $enseignant): void
    {
        $user = $enseignant->getUsers();
        if ($user === null) {
            $user = new Users();
            $user->setIsVerified(true);
            $enseignant->setUsers($user);
        }

        $user->setUsername($enseignant->getUsername() ?? 'user_' . uniqid());
        $user->setEmail($enseignant->getMailUniv() ?? $enseignant->getMailPerso());

        // Garantir l'unicité (enseignant, departement) et au plus un département par défaut
        $seenDeptIds = [];
        $hasDefault = false;
        $hasEditeur = false;
        $hasEvaluateur = false;

        foreach ($enseignant->getEnseignantDepartements()->toArray() as $ed) {
            $dept = $ed->getDepartement();
            if ($dept === null) {
                $enseignant->removeEnseignantDepartement($ed);
                continue;
            }

            $deptKey = $dept->getId() ?? spl_object_id($dept);
            if (isset($seenDeptIds[$deptKey])) {
                $enseignant->removeEnseignantDepartement($ed);
                continue;
            }
            $seenDeptIds[$deptKey] = true;

            $ed->setEnseignant($enseignant);

            if ($ed->isDefaut()) {
                if ($hasDefault) {
                    $ed->setDefaut(false);
                } else {
                    $hasDefault = true;
                }
            }

            if ($ed->getRole() === DepartementRoleEnum::EDITEUR) {
                $hasEditeur = true;
            } else {
                $hasEvaluateur = true;
            }
        }

        // Synchroniser les rôles globaux Symfony cohérents avec les rôles par département
        $roles = array_values(array_filter(
            $enseignant->getRoles(),
            static fn (string $r): bool => $r !== 'ROLE_USER'
        ));

        if ($hasEditeur && !in_array('ROLE_EDITEUR', $roles, true)) {
            $roles[] = 'ROLE_EDITEUR';
        } elseif ($hasEvaluateur && !in_array('ROLE_EVALUATEUR', $roles, true) && !in_array('ROLE_EDITEUR', $roles, true)) {
            $roles[] = 'ROLE_EVALUATEUR';
        }

        if (empty($roles)) {
            $roles[] = 'ROLE_EVALUATEUR';
        }

        $user->setRoles(array_values(array_unique($roles)));

        $plainPassword = $this->getSubmittedPassword();
        if ($plainPassword !== null) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        }
    }

    private function getSubmittedPassword(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return null;
        }

        $data = $request->request->all('Enseignant');
        if (isset($data['password']) && is_string($data['password']) && '' !== trim($data['password'])) {
            return trim($data['password']);
        }

        foreach ($request->request->all() as $value) {
            if (is_array($value) && isset($value['password']) && is_string($value['password']) && '' !== trim($value['password'])) {
                return trim($value['password']);
            }
        }

        return null;
    }
}
