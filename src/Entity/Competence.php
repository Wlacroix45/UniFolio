<?php

/*
 * Copyright (c) 2023. | Cyndel Herolt | IUT de Troyes  - All Rights Reserved
 * @author cyndelherolt
 * @project UniFolio
 */
namespace App\Entity;

use App\Repository\CompetenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompetenceRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_competence_referentiel_numero', columns: ['referentiel_id', 'numero'])]
class Competence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true, unique: true)]
    private ?int $intranetId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $libelle = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nom_court = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $couleur = null;

    #[ORM\Column(nullable: true)]
    private ?int $numero = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $ue = null;

    #[ORM\ManyToOne(inversedBy: 'competences')]
    private ?ApcReferentiel $referentiel = null;

    #[ORM\OneToMany(mappedBy: 'competences', targetEntity: ApcNiveau::class, cascade: ['persist'])]
    private Collection $apcNiveaux;

    /**
     * @var Collection<int, ApcComposanteEssentielle>
     */
    #[ORM\OneToMany(mappedBy: 'competence', targetEntity: ApcComposanteEssentielle::class)]
    private Collection $composantesEssentielles;

    /**
     * @var Collection<int, ApcSituationProfessionnelle>
     */
    #[ORM\OneToMany(mappedBy: 'competence', targetEntity: ApcSituationProfessionnelle::class)]
    private Collection $situationsProfessionnelles;

    public function __construct()
    {
        $this->validations = new ArrayCollection();
        $this->apcNiveaux = new ArrayCollection();
        $this->composantesEssentielles = new ArrayCollection();
        $this->situationsProfessionnelles = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->libelle ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIntranetId(): ?int
    {
        return $this->intranetId;
    }

    public function setIntranetId(?int $intranetId): self
    {
        $this->intranetId = $intranetId;

        return $this;
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

    public function getNomCourt(): ?string
    {
        return $this->nom_court;
    }

    public function setNomCourt(?string $nom_court): self
    {
        $this->nom_court = $nom_court;

        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): self
    {
        $this->couleur = $couleur;

        return $this;
    }

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(?int $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getUe(): ?string
    {
        return $this->ue;
    }

    public function setUe(?string $ue): self
    {
        $this->ue = $ue;

        return $this;
    }

    public function getReferentiel(): ?ApcReferentiel
    {
        return $this->referentiel;
    }

    public function setReferentiel(?ApcReferentiel $referentiel): self
    {
        $this->referentiel = $referentiel;

        return $this;
    }

    /**
     * @return Collection<int, ApcNiveau>
     */
    public function getApcNiveaux(): Collection
    {
        return $this->apcNiveaux;
    }

    public function addApcNiveau(ApcNiveau $apcNiveau): self
    {
        if (!$this->apcNiveaux->contains($apcNiveau)) {
            $this->apcNiveaux->add($apcNiveau);
            $apcNiveau->setCompetences($this);
        }

        return $this;
    }

    public function removeApcNiveau(ApcNiveau $apcNiveau): self
    {
        if ($this->apcNiveaux->removeElement($apcNiveau)) {
            // set the owning side to null (unless already changed)
            if ($apcNiveau->getCompetences() === $this) {
                $apcNiveau->setCompetences(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ApcComposanteEssentielle>
     */
    public function getComposantesEssentielles(): Collection
    {
        return $this->composantesEssentielles;
    }

    public function addComposanteEssentielle(ApcComposanteEssentielle $composanteEssentielle): self
    {
        if (!$this->composantesEssentielles->contains($composanteEssentielle)) {
            $this->composantesEssentielles->add($composanteEssentielle);
            $composanteEssentielle->setCompetence($this);
        }

        return $this;
    }

    public function removeComposanteEssentielle(ApcComposanteEssentielle $composanteEssentielle): self
    {
        if ($this->composantesEssentielles->removeElement($composanteEssentielle) && $composanteEssentielle->getCompetence() === $this) {
            $composanteEssentielle->setCompetence(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, ApcSituationProfessionnelle>
     */
    public function getSituationsProfessionnelles(): Collection
    {
        return $this->situationsProfessionnelles;
    }

    public function addSituationProfessionnelle(ApcSituationProfessionnelle $situationProfessionnelle): self
    {
        if (!$this->situationsProfessionnelles->contains($situationProfessionnelle)) {
            $this->situationsProfessionnelles->add($situationProfessionnelle);
            $situationProfessionnelle->setCompetence($this);
        }

        return $this;
    }

    public function removeSituationProfessionnelle(ApcSituationProfessionnelle $situationProfessionnelle): self
    {
        if ($this->situationsProfessionnelles->removeElement($situationProfessionnelle) && $situationProfessionnelle->getCompetence() === $this) {
            $situationProfessionnelle->setCompetence(null);
        }

        return $this;
    }
}
