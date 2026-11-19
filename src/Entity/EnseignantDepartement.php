<?php

/*
 * Copyright (c) 2023. | Cyndel Herolt | IUT de Troyes  - All Rights Reserved
 * @author cyndelherolt
 * @project UniFolio
 */
namespace App\Entity;

use App\Enum\DepartementRoleEnum;
use App\Repository\EnseignantDepartementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EnseignantDepartementRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_enseignant_departement', columns: ['enseignant_id', 'departement_id'])]
class EnseignantDepartement extends BaseEntity
{
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $defaut = false;

    /**
     * Rôle spécifique de l'enseignant pour ce département (Évaluateur ou Éditeur).
     */
    #[ORM\Column(
        length: 50,
        nullable: false,
        enumType: DepartementRoleEnum::class,
        options: ['default' => 'ROLE_EVALUATEUR']
    )]
    private DepartementRoleEnum $role = DepartementRoleEnum::EVALUATEUR;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Enseignant::class, inversedBy: 'enseignantDepartements')]
        private ?Enseignant $enseignant = null,
        #[ORM\ManyToOne(targetEntity: Departement::class, inversedBy: 'enseignantDepartements')]
        private ?Departement $departement = null,
        DepartementRoleEnum $role = DepartementRoleEnum::EVALUATEUR
    ) {
        $this->role = $role;
    }

    public function getRole(): DepartementRoleEnum
    {
        return $this->role;
    }

    public function setRole(DepartementRoleEnum $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function isEditeur(): bool
    {
        return $this->role === DepartementRoleEnum::EDITEUR;
    }

    public function isEvaluateur(): bool
    {
        return $this->role === DepartementRoleEnum::EVALUATEUR || $this->role === DepartementRoleEnum::EDITEUR;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return [$this->role->value];
    }

    public function addRole(string|DepartementRoleEnum $role): self
    {
        if ($role instanceof DepartementRoleEnum) {
            $this->role = $role;
        } else {
            $resolved = DepartementRoleEnum::tryFrom($role);
            if ($resolved !== null) {
                $this->role = $resolved;
            }
        }

        return $this;
    }

    public function removeRole(string $role): self
    {
        if ($this->role->value === $role) {
            $this->role = DepartementRoleEnum::EVALUATEUR;
        }

        return $this;
    }

    public function getEnseignant(): ?Enseignant
    {
        return $this->enseignant;
    }

    public function setEnseignant(?Enseignant $enseignant): self
    {
        $this->enseignant = $enseignant;

        return $this;
    }

    public function getDepartement(): ?Departement
    {
        return $this->departement;
    }

    public function setDepartement(?Departement $departement): self
    {
        $this->departement = $departement;

        return $this;
    }

    public function getDefaut(): bool
    {
        return $this->defaut;
    }

    public function isDefaut(): bool
    {
        return $this->defaut;
    }

    public function setDefaut(bool $defaut): self
    {
        $this->defaut = $defaut;

        return $this;
    }

    public function __toString(): string
    {
        $dept = $this->departement?->getLibelle() ?? 'Département';
        $role = $this->role->getLibelle();
        $defaut = $this->defaut ? ' [Défaut]' : '';

        return sprintf('%s (%s)%s', $dept, $role, $defaut);
    }
}
