<?php

/*
 * Copyright (c) 2023. | Cyndel Herolt | IUT de Troyes  - All Rights Reserved
 * @author cyndelherolt
 * @project UniFolio
 */
namespace App\Repository;

use App\Entity\ApcNiveau;
use App\Entity\Departement;
use App\Entity\Etudiant;
use App\Entity\Portfolio;
use App\Entity\Validation;
use App\Enum\EvaluationStatutEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Portfolio>
 *
 * @method Portfolio|null find($id, $lockMode = null, $lockVersion = null)
 * @method Portfolio|null findOneBy(array $criteria, array $orderBy = null)
 * @method Portfolio[]    findAll()
 * @method Portfolio[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PortfolioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Portfolio::class);
    }

    public function save(Portfolio $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Portfolio $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    private function createEvaluationQb(Departement $departement, EvaluationStatutEnum $statut, array $filtres): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.etudiant', 'e')
            ->innerJoin('e.semestre', 's')
            ->innerJoin('s.annee', 'a')
            ->innerJoin('a.diplome', 'd')
            ->where('d.departement = :departement')
            ->andWhere('p.visibilite = true')
            ->setParameter('departement', $departement);

        $demarree = 'EXISTS (SELECT 1 FROM ' . Validation::class . ' vd'
            . ' JOIN vd.trace td JOIN td.ordreTrace otd JOIN otd.page pad JOIN pad.ordrePage opd'
            . ' WHERE opd.portfolio = p AND vd.etat <> 0)';

        match ($statut) {
            EvaluationStatutEnum::A_EVALUER => $qb
                ->andWhere('p.dateValidationEvaluation IS NULL')
                ->andWhere('p.dateBrouillonEvaluation IS NULL')
                ->andWhere('NOT ' . $demarree),
            EvaluationStatutEnum::EN_COURS => $qb
                ->andWhere('p.dateValidationEvaluation IS NULL')
                ->andWhere('p.dateBrouillonEvaluation IS NOT NULL OR ' . $demarree),
            EvaluationStatutEnum::EVALUE => $qb
                ->andWhere('p.dateValidationEvaluation IS NOT NULL'),
        };

        if (!empty($filtres['semestre'])) {
            $qb->andWhere('s.id = :semestre')
                ->andWhere('p.annee = s.annee')
                ->setParameter('semestre', $filtres['semestre']);
        }
        if (!empty($filtres['groupe'])) {
            $qb->andWhere(':groupe MEMBER OF e.groupe')
                ->setParameter('groupe', $filtres['groupe']);
        }
        if (!empty($filtres['etudiant'])) {
            $qb->andWhere('e.id = :etudiant')
                ->setParameter('etudiant', $filtres['etudiant']);
        }
        if (!empty($filtres['niveau'])) {
            $qb->andWhere('EXISTS (SELECT 1 FROM ' . Validation::class . ' vn'
                . ' JOIN vn.trace tn JOIN tn.ordreTrace otn JOIN otn.page pan JOIN pan.ordrePage opn'
                . ' WHERE opn.portfolio = p AND vn.apcNiveau = :niveau)')
                ->setParameter('niveau', $filtres['niveau']);
        }

        return $qb;
    }

    public function countForEvaluation(Departement $departement, EvaluationStatutEnum $statut, array $filtres = []): int
    {
        return (int) $this->createEvaluationQb($departement, $statut, $filtres)
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findForEvaluation(
        Departement $departement,
        EvaluationStatutEnum $statut,
        array $filtres = [],
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->createEvaluationQb($departement, $statut, $filtres)
            ->addSelect('e', 's', 'a')
            ->orderBy('p.date_modification', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findProgression(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = $this->getEntityManager()->createQuery(
            'SELECT IDENTITY(op.portfolio) AS id, COUNT(v.id) AS total,'
            . ' SUM(CASE WHEN v.etat <> 0 THEN 1 ELSE 0 END) AS evaluees'
            . ' FROM ' . Validation::class . ' v'
            . ' JOIN v.trace t JOIN t.ordreTrace ot JOIN ot.page pa JOIN pa.ordrePage op'
            . ' WHERE op.portfolio IN (:ids)'
            . ' GROUP BY op.portfolio'
        )->setParameter('ids', $ids)->getArrayResult();

        $progression = [];
        foreach ($rows as $row) {
            $progression[(int) $row['id']] = ['total' => (int) $row['total'], 'evaluees' => (int) $row['evaluees']];
        }

        return $progression;
    }

    public function findEtudiantsAvecPortfolio(Departement $departement): array
    {
        return $this->getEntityManager()->createQuery(
            'SELECT e FROM ' . Etudiant::class . ' e'
            . ' JOIN e.semestre s JOIN s.annee a JOIN a.diplome d'
            . ' WHERE d.departement = :departement'
            . ' AND EXISTS (SELECT 1 FROM ' . Portfolio::class . ' p WHERE p.etudiant = e AND p.visibilite = true)'
            . ' ORDER BY e.nom ASC, e.prenom ASC'
        )->setParameter('departement', $departement)->getResult();
    }

    public function findNiveauxRevendiques(Departement $departement): array
    {
        return $this->getEntityManager()->createQuery(
            'SELECT n FROM ' . ApcNiveau::class . ' n WHERE EXISTS ('
            . 'SELECT 1 FROM ' . Validation::class . ' v JOIN v.trace t JOIN t.bibliotheque b'
            . ' JOIN b.etudiant e JOIN e.semestre s JOIN s.annee a JOIN a.diplome d'
            . ' WHERE v.apcNiveau = n AND d.departement = :departement'
            . ') ORDER BY n.libelle ASC'
        )->setParameter('departement', $departement)->getResult();
    }


//    /**
//     * @return Portfolio[] Returns an array of Portfolio objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Portfolio
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
