<?php

namespace App\Repository;

use App\Entity\TemplateSection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TemplateSection>
 *
 * @method TemplateSection|null find($id, $lockMode = null, $lockVersion = null)
 * @method TemplateSection|null findOneBy(array $criteria, array $orderBy = null)
 * @method TemplateSection[]    findAll()
 * @method TemplateSection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TemplateSectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TemplateSection::class);
    }

    public function save(TemplateSection $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TemplateSection $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
