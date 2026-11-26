<?php

namespace App\Entity;

use App\Repository\TemplateSectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TemplateSectionRepository::class)]
class TemplateSection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PortfolioTemplate::class, inversedBy: 'sections')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PortfolioTemplate $portfolioTemplate = null;

    #[ORM\Column]
    #[Assert\Positive(message: 'L\'ordre doit être supérieur à 0.')]
    private int $ordre = 1;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Veuillez saisir un titre de section.')]
    #[Assert\Length(max: 255, maxMessage: 'Le titre de section ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $consigne = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $questionReflexivePrompt = null;

    /**
     * @var Collection<int, ApcNiveau>
     */
    #[ORM\ManyToMany(targetEntity: ApcNiveau::class)]
    #[ORM\JoinTable(name: 'template_section_apc_niveau')]
    private Collection $competencesCibles;

    public function __construct()
    {
        $this->competencesCibles = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->titre ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPortfolioTemplate(): ?PortfolioTemplate
    {
        return $this->portfolioTemplate;
    }

    public function setPortfolioTemplate(?PortfolioTemplate $portfolioTemplate): static
    {
        $this->portfolioTemplate = $portfolioTemplate;

        return $this;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getConsigne(): ?string
    {
        return $this->consigne;
    }

    public function setConsigne(?string $consigne): static
    {
        $this->consigne = $consigne;

        return $this;
    }

    public function getQuestionReflexivePrompt(): ?string
    {
        return $this->questionReflexivePrompt;
    }

    public function setQuestionReflexivePrompt(?string $questionReflexivePrompt): static
    {
        $this->questionReflexivePrompt = $questionReflexivePrompt;

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

    // Alias requis par Symfony PropertyAccess sur le pluriel français "competencesCibles"
    public function addCompetencesCible(ApcNiveau $niveau): static
    {
        return $this->addCompetenceCible($niveau);
    }

    public function removeCompetencesCible(ApcNiveau $niveau): static
    {
        return $this->removeCompetenceCible($niveau);
    }
}
