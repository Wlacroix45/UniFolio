<?php

namespace App\Repository;

use App\Entity\Annee;
use App\Entity\ApcParcours;
use App\Entity\Departement;
use App\Entity\PortfolioTemplate;
use App\Enum\PortfolioTemplateStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PortfolioTemplate>
 *
 * @method PortfolioTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method PortfolioTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method PortfolioTemplate[]    findAll()
 * @method PortfolioTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PortfolioTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PortfolioTemplate::class);
    }

    public function save(PortfolioTemplate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PortfolioTemplate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return PortfolioTemplate[]
     */
    public function findByDepartement(
        Departement $departement,
        ?PortfolioTemplateStatusEnum $statut = null
    ): array {
        $qb = $this->createQueryBuilder('pt')
            ->andWhere('pt.departement = :departement')
            ->setParameter('departement', $departement)
            ->orderBy('pt.updatedAt', 'DESC')
            ->addOrderBy('pt.createdAt', 'DESC');

        if ($statut !== null) {
            $qb->andWhere('pt.statut = :statut')
                ->setParameter('statut', $statut);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return PortfolioTemplate[]
     */
    public function findPublishedForStudent(
        Departement $departement,
        ?Annee $annee = null,
        ?ApcParcours $parcours = null
    ): array {
        $qb = $this->createQueryBuilder('pt')
            ->andWhere('pt.departement = :departement')
            ->andWhere('pt.statut = :statut')
            ->setParameter('departement', $departement)
            ->setParameter('statut', PortfolioTemplateStatusEnum::PUBLIE)
            ->orderBy('pt.nom', 'ASC');

        if ($annee !== null) {
            $qb->andWhere('pt.annee IS NULL OR pt.annee = :annee')
                ->setParameter('annee', $annee);
        }

        if ($parcours !== null) {
            $qb->andWhere('pt.apcParcours IS NULL OR pt.apcParcours = :parcours')
                ->setParameter('parcours', $parcours);
        } else {
            $qb->andWhere('pt.apcParcours IS NULL');
        }

        return $qb->getQuery()->getResult();
    }
}
