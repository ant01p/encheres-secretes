<?php

namespace App\Repository;

use App\Entity\Item;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Item>
 */
class ItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    //    /**
    //     * @return Item[] Returns an array of Item objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('i.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Item
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
   public function findItemsByStatus(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.status IN (:statuses)')
            ->setParameter('statuses', ['published', 'unpublished'])
            ->orderBy('i.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findItemsByCategory(Category $category): array
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.categories', 'c')
            ->where('c = :category')
            ->andWhere('i.status IN (:statuses)')
            ->setParameter('category', $category)
            ->setParameter('statuses', ['published', 'unpublished'])
            ->orderBy('i.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
