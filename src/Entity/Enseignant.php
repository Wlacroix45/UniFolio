<?php

/*
 * Copyright (c) 2023. | Cyndel Herolt | IUT de Troyes  - All Rights Reserved
 * @author cyndelherolt
 * @project UniFolio
 */
namespace App\Entity;

use App\Enum\DepartementRoleEnum;
use App\Repository\EnseignantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EnseignantRepository::class)]
class Enseignant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mail_univ = null;

    #[ORM\OneToMany(mappedBy: 'enseignant', targetEntity: Validation::class)]
    private Collection $validations;

    #[ORM\OneToMany(mappedBy: 'enseignant', targetEntity: Commentaire::class)]
    private Collection $commentaires;

    #[ORM\OneToOne(mappedBy: 'enseignant', targetEntity: Users::class, cascade: ['persist', 'remove'])]
    private ?Users $users = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mail_perso = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    /**
     * @var Collection<int, EnseignantDepartement>
     */
    #[ORM\OneToMany(
        mappedBy: 'enseignant',
        targetEntity: EnseignantDepartement::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $enseignantDepartements;

    #[ORM\Column(length: 75, nullable: true)]
    private ?string $username = null;

    #[ORM\OneToMany(mappedBy: 'enseignant', targetEntity: Notification::class)]
    private Collection $notifications;

    public function __construct()
    {
        $this->validations = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->enseignantDepartements = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getMailUniv(): ?string
    {
        return $this->mail_univ;
    }

    public function setMailUniv(?string $mail_univ): self
    {
        $this->mail_univ = $mail_univ;

        return $this;
    }

    /**
     * @return Collection<int, Validation>
     */
    public function getValidations(): Collection
    {
        return $this->validations;
    }

    public function addValidation(Validation $validation): self
    {
        if (!$this->validations->contains($validation)) {
            $this->validations->add($validation);
            $validation->setEnseignant($this);
        }

        return $this;
    }

    public function removeValidation(Validation $validation): self
    {
        if ($this->validations->removeElement($validation)) {
            // set the owning side to null (unless already changed)
            if ($validation->getEnseignant() === $this) {
                $validation->setEnseignant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): self
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setEnseignant($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): self
    {
        if ($this->commentaires->removeElement($commentaire)) {
            // set the owning side to null (unless already changed)
            if ($commentaire->getEnseignant() === $this) {
                $commentaire->setEnseignant(null);
            }
        }

        return $this;
    }

    public function getUsers(): ?Users
    {
        return $this->users;
    }

    public function setUsers(?Users $users): self
    {
        // unset the owning side of the relation if necessary
        if ($users === null && $this->users !== null) {
            $this->users->setEnseignant(null);
        }

        // set the owning side of the relation if necessary
        if ($users !== null && $users->getEnseignant() !== $this) {
            $users->setEnseignant($this);
        }

        $this->users = $users;

        return $this;
    }

    public function getMailPerso(): ?string
    {
        return $this->mail_perso;
    }

    public function setMailPerso(?string $mail_perso): self
    {
        $this->mail_perso = $mail_perso;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * @return Collection|EnseignantDepartement[]
     */
    public function getEnseignantDepartements(): Collection
    {
        return $this->enseignantDepartements;
    }

    public function addEnseignantDepartement(EnseignantDepartement $enseignantDepartement): self
    {
        if (!$this->enseignantDepartements->contains($enseignantDepartement)) {
            $this->enseignantDepartements[] = $enseignantDepartement;
            $enseignantDepartement->setEnseignant($this);
        }

        return $this;
    }

    public function removeEnseignantDepartement(EnseignantDepartement $enseignantDepartement): self
    {
        if ($this->enseignantDepartements->contains($enseignantDepartement)) {
            $this->enseignantDepartements->removeElement($enseignantDepartement);
            // set the owning side to null (unless already changed)
            if ($enseignantDepartement->getEnseignant() === $this) {
                $enseignantDepartement->setEnseignant(null);
            }
        }

        return $this;
    }

    public function addDepartement(
        Departement $departement,
        DepartementRoleEnum $role = DepartementRoleEnum::EVALUATEUR,
        bool $defaut = false
    ): self {
        $existing = $this->getEnseignantDepartement($departement);
        if ($existing !== null) {
            $existing->setRole($role);
            $existing->setDefaut($defaut);

            return $this;
        }

        $enseignantDepartement = new EnseignantDepartement($this, $departement, $role);
        $enseignantDepartement->setDefaut($defaut);
        $this->addEnseignantDepartement($enseignantDepartement);

        return $this;
    }

    public function removeDepartement(Departement $departement): self
    {
        $existing = $this->getEnseignantDepartement($departement);
        if ($existing !== null) {
            $this->removeEnseignantDepartement($existing);
        }

        return $this;
    }

    /**
     * @return Collection<int, Departement>
     */
    public function getDepartements(): Collection
    {
        $departements = new ArrayCollection();
        foreach ($this->enseignantDepartements as $ed) {
            $dept = $ed->getDepartement();
            if ($dept !== null && !$departements->contains($dept)) {
                $departements->add($dept);
            }
        }

        return $departements;
    }

    public function getEnseignantDepartement(Departement $departement): ?EnseignantDepartement
    {
        foreach ($this->enseignantDepartements as $ed) {
            $dept = $ed->getDepartement();
            if ($dept === null) {
                continue;
            }
            if ($dept === $departement || ($dept->getId() !== null && $dept->getId() === $departement->getId())) {
                return $ed;
            }
        }

        return null;
    }

    public function getRoleForDepartement(Departement $departement): ?DepartementRoleEnum
    {
        return $this->getEnseignantDepartement($departement)?->getRole();
    }

    public function hasRoleInDepartement(Departement $departement, string|DepartementRoleEnum $role): bool
    {
        $ed = $this->getEnseignantDepartement($departement);
        if ($ed === null) {
            return false;
        }

        $roleValue = $role instanceof DepartementRoleEnum ? $role->value : $role;

        if ($roleValue === DepartementRoleEnum::EVALUATEUR->value) {
            return $ed->isEvaluateur();
        }

        if ($roleValue === DepartementRoleEnum::EDITEUR->value) {
            return $ed->isEditeur();
        }

        return false;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->users?->getRoles() ?? ['ROLE_EVALUATEUR'];
    }

    /**
     * @param string[] $roles
     */
    public function setRoles(array $roles): self
    {
        if ($this->users === null) {
            $user = new Users();
            $user->setUsername($this->username ?? $this->mail_univ ?? 'user_' . uniqid());
            $user->setEmail($this->mail_univ ?? $this->mail_perso);
            $user->setIsVerified(true);
            $this->setUsers($user);
        }

        $this->users->setRoles(array_values(array_unique($roles)));

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setEnseignant($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getEnseignant() === $this) {
                $notification->setEnseignant(null);
            }
        }

        return $this;
    }
}
