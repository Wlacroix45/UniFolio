<?php

namespace App\Entity;

use App\Enum\PortfolioTemplateStatusEnum;
use App\Repository\PortfolioTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PortfolioTemplateRepository::class)]
class PortfolioTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Veuillez saisir un nom de modèle.')]
    #[Assert\Length(max: 255, maxMessage: 'Le nom du modèle ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Departement::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Departement $departement = null;

    #[ORM\ManyToOne(targetEntity: ApcReferentiel::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ApcReferentiel $apcReferentiel = null;

    #[ORM\ManyToOne(targetEntity: Annee::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Annee $annee = null;

    #[ORM\ManyToOne(targetEntity: ApcParcours::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ApcParcours $apcParcours = null;

    /**
     * @var Collection<int, ApcNiveau>
     */
    #[ORM\ManyToMany(targetEntity: ApcNiveau::class)]
    #[ORM\JoinTable(name: 'portfolio_template_apc_niveau')]
    private Collection $competencesCibles;

    #[ORM\Column(length: 30, enumType: PortfolioTemplateStatusEnum::class)]
    private PortfolioTemplateStatusEnum $statut = PortfolioTemplateStatusEnum::BROUILLON;

    #[ORM\ManyToOne(targetEntity: Enseignant::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Enseignant $enseignant = null;

    /**
     * @var Collection<int, TemplateSection>
     */
    #[ORM\OneToMany(
        mappedBy: 'portfolioTemplate',
        targetEntity: TemplateSection::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    #[Assert\Valid]
    private Collection $sections;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->competencesCibles = new ArrayCollection();
        $this->sections = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDepartement(): ?Departement
    {
        return $this->departement;
    }

    public function setDepartement(?Departement $departement): static
    {
        $this->departement = $departement;

        return $this;
    }

    public function getApcReferentiel(): ?ApcReferentiel
    {
        return $this->apcReferentiel;
    }

    public function setApcReferentiel(?ApcReferentiel $apcReferentiel): static
    {
        $this->apcReferentiel = $apcReferentiel;

        return $this;
    }

    public function getAnnee(): ?Annee
    {
        return $this->annee;
    }

    public function setAnnee(?Annee $annee): static
    {
        $this->annee = $annee;

        return $this;
    }

    public function getApcParcours(): ?ApcParcours
    {
        return $this->apcParcours;
    }

    public function setApcParcours(?ApcParcours $apcParcours): static
    {
        $this->apcParcours = $apcParcours;

        return $this;
    }

    /**
     * @return Collection<int, ApcNiveau>
     */
    public function getCompetencesCibles(): Collection
    {
        return $this->competencesCibles;
    }

    public function addCompetenceCible(ApcNiveau $niveau): static
    {
        if (!$this->competencesCibles->contains($niveau)) {
            $this->competencesCibles->add($niveau);
        }

        return $this;
    }

    public function removeCompetenceCible(ApcNiveau $niveau): static
    {
        $this->competencesCibles->removeElement($niveau);

        return $this;
    }

    public function addCompetencesCible(ApcNiveau $niveau): static
    {
        return $this->addCompetenceCible($niveau);
    }

    public function removeCompetencesCible(ApcNiveau $niveau): static
    {
        return $this->removeCompetenceCible($niveau);
    }

    public function getStatut(): PortfolioTemplateStatusEnum
    {
        return $this->statut;
    }

    public function setStatut(PortfolioTemplateStatusEnum $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function isDraft(): bool
    {
        return $this->statut === PortfolioTemplateStatusEnum::BROUILLON;
    }

    public function isPublished(): bool
    {
        return $this->statut === PortfolioTemplateStatusEnum::PUBLIE;
    }

    public function isArchived(): bool
    {
        return $this->statut === PortfolioTemplateStatusEnum::ARCHIVE;
    }

    public function canEdit(): bool
    {
        return $this->statut !== PortfolioTemplateStatusEnum::ARCHIVE;
    }

    public function canPublish(): bool
    {
        return $this->statut === PortfolioTemplateStatusEnum::BROUILLON;
    }

    public function canArchive(): bool
    {
        return $this->statut !== PortfolioTemplateStatusEnum::ARCHIVE;
    }

    public function publish(): static
    {
        if (!$this->canPublish()) {
            throw new \LogicException('Seul un modèle en brouillon peut être publié.');
        }

        if ($this->sections->isEmpty()) {
            throw new \LogicException('Un modèle doit contenir au moins une section avant d\'être publié.');
        }

        $this->statut = PortfolioTemplateStatusEnum::PUBLIE;
        $this->touch();

        return $this;
    }

    public function archive(): static
    {
        if (!$this->canArchive()) {
            throw new \LogicException('Ce modèle est déjà archivé.');
        }

        $this->statut = PortfolioTemplateStatusEnum::ARCHIVE;
        $this->touch();

        return $this;
    }

    public function getEnseignant(): ?Enseignant
    {
        return $this->enseignant;
    }

    public function setEnseignant(?Enseignant $enseignant): static
    {
        $this->enseignant = $enseignant;

        return $this;
    }

    /**
     * @return Collection<int, TemplateSection>
     */
    public function getSections(): Collection
    {
        return $this->sections;
    }

    /**
     * @return list<TemplateSection>
     */
    public function getOrderedSections(): array
    {
        $sections = $this->sections->toArray();
        usort(
            $sections,
            static fn (TemplateSection $a, TemplateSection $b): int =>
                [$a->getOrdre(), $a->getId() ?? 0] <=> [$b->getOrdre(), $b->getId() ?? 0]
        );

        return array_values($sections);
    }

    public function addSection(TemplateSection $section): static
    {
        if (!$this->sections->contains($section)) {
            $this->sections->add($section);
            $section->setPortfolioTemplate($this);
        }

        return $this;
    }

    public function removeSection(TemplateSection $section): static
    {
        if ($this->sections->removeElement($section)) {
            if ($section->getPortfolioTemplate() === $this) {
                $section->setPortfolioTemplate(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function touch(): static
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
