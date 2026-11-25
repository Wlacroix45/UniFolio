<?php

namespace App\Repository;

use App\Entity\ApcComposanteEssentielle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApcComposanteEssentielle>
 *
 * @method ApcComposanteEssentielle|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApcComposanteEssentielle|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApcComposanteEssentielle[]    findAll()
 * @method ApcComposanteEssentielle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApcComposanteEssentielleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApcComposanteEssentielle::class);
    }

    public function save(ApcComposanteEssentielle $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ApcComposanteEssentielle $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
