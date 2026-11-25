<?php

namespace App\Entity;

use App\Repository\ApcSaeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApcSaeRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_sae_semestre_code_matiere', columns: ['semestre_id', 'code_matiere'])]
class ApcSae
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $libelle = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $codeMatiere = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $portfolio = false;

    #[ORM\ManyToOne]
    private ?Semestre $semestre = null;

    #[ORM\ManyToMany(targetEntity: ApcApprentissageCritique::class)]
    #[ORM\JoinTable(name: 'apc_sae_apprentissage_critique')]
    private Collection $apprentissagesCritiques;

    public function __construct()
    {
        $this->apprentissagesCritiques = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->libelle ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getCodeMatiere(): ?string
    {
        return $this->codeMatiere;
    }

    public function setCodeMatiere(?string $codeMatiere): self
    {
        $this->codeMatiere = $codeMatiere;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function isPortfolio(): bool
    {
        return $this->portfolio;
    }

    public function setPortfolio(bool $portfolio): self
    {
        $this->portfolio = $portfolio;

        return $this;
    }

    public function getSemestre(): ?Semestre
    {
        return $this->semestre;
    }

    public function setSemestre(?Semestre $semestre): self
    {
        $this->semestre = $semestre;

        return $this;
    }

    public function getApprentissagesCritiques(): Collection
    {
        return $this->apprentissagesCritiques;
    }

    public function addApprentissageCritique(ApcApprentissageCritique $apprentissageCritique): self
    {
        if (!$this->apprentissagesCritiques->contains($apprentissageCritique)) {
            $this->apprentissagesCritiques->add($apprentissageCritique);
        }

        return $this;
    }

    public function removeApprentissageCritique(ApcApprentissageCritique $apprentissageCritique): self
    {
        $this->apprentissagesCritiques->removeElement($apprentissageCritique);

        return $this;
    }
}
