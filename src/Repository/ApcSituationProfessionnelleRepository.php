<?php

namespace App\Repository;

use App\Entity\ApcSituationProfessionnelle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApcSituationProfessionnelle>
 *
 * @method ApcSituationProfessionnelle|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApcSituationProfessionnelle|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApcSituationProfessionnelle[]    findAll()
 * @method ApcSituationProfessionnelle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApcSituationProfessionnelleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApcSituationProfessionnelle::class);
    }

    public function save(ApcSituationProfessionnelle $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ApcSituationProfessionnelle $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
