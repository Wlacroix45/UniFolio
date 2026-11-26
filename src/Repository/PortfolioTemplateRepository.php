<?php

namespace App\Repository;

use App\Entity\Departement;
use App\Entity\PortfolioTemplate;
use App\Enum\PortfolioTemplateStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PortfolioTemplate>
 */
class PortfolioTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PortfolioTemplate::class);
    }

    /**
     * @return PortfolioTemplate[]
     */
    public function findByDepartement(
        Departement $departement,
        ?PortfolioTemplateStatusEnum $statut = null
    ): array {
        $qb = $this->createQueryBuilder('pt')
            ->leftJoin('pt.annee', 'a')->addSelect('a')
            ->leftJoin('pt.apcParcours', 'p')->addSelect('p')
            ->leftJoin('pt.apcReferentiel', 'r')->addSelect('r')
            ->leftJoin('pt.sections', 's')->addSelect('s')
            ->andWhere('pt.departement = :departement')
            ->setParameter('departement', $departement)
            ->orderBy('pt.updatedAt', 'DESC')
            ->addOrderBy('pt.createdAt', 'DESC')
            ->addOrderBy('s.ordre', 'ASC');

        if ($statut !== null) {
            $qb->andWhere('pt.statut = :statut')
                ->setParameter('statut', $statut);
        }

        return $qb->getQuery()->getResult();
    }
}
