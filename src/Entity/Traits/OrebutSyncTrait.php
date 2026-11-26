<?php

namespace App\Entity\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Champs de synchronisation avec OréBUT.
 *
 * - orebutId : identifiant de l'élément dans OréBUT, pour retrouver l'élément même si son code change.
 * - orebutSnapshot : dernières valeurs reçues d'OréBUT. Une valeur locale différente du snapshot
 *   est une adaptation locale et ne doit pas être écrasée par la synchronisation.
 * - orebutSupprimeLe : date à laquelle l'élément a disparu d'OréBUT. L'élément est conservé en base.
 */
trait OrebutSyncTrait
{
    #[ORM\Column(nullable: true, unique: true)]
    private ?int $orebutId = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $orebutSnapshot = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $orebutSupprimeLe = null;

    public function getOrebutId(): ?int
    {
        return $this->orebutId;
    }

    public function setOrebutId(?int $orebutId): self
    {
        $this->orebutId = $orebutId;

        return $this;
    }

    public function getOrebutSnapshot(): ?array
    {
        return $this->orebutSnapshot;
    }

    public function setOrebutSnapshot(?array $orebutSnapshot): self
    {
        $this->orebutSnapshot = $orebutSnapshot;

        return $this;
    }

    public function getOrebutSupprimeLe(): ?\DateTimeImmutable
    {
        return $this->orebutSupprimeLe;
    }

    public function setOrebutSupprimeLe(?\DateTimeImmutable $orebutSupprimeLe): self
    {
        $this->orebutSupprimeLe = $orebutSupprimeLe;

        return $this;
    }

    public function isSupprimeOrebut(): bool
    {
        return null !== $this->orebutSupprimeLe;
    }
}
