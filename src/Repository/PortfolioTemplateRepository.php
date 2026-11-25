<?php

namespace App\Repository;

use App\Entity\Departement;
use App\Entity\PortfolioTemplate;
use App\Enum\PortfolioTemplateStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Accès aux modèles de portfolio guidé en base de données.
 *
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
     * Retourne les modèles d'un département avec préchargement des relations affichées dans la liste
     * afin d'éviter les requêtes N+1 côté Twig.
     *
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
            ->leftJoin('pt.competencesCibles', 'cc')->addSelect('cc')
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
