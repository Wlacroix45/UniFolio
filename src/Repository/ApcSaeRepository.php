<?php

namespace App\Repository;

use App\Entity\ApcSae;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApcSae>
 *
 * @method ApcSae|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApcSae|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApcSae[]    findAll()
 * @method ApcSae[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApcSaeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApcSae::class);
    }

    public function save(ApcSae $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ApcSae $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
