<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findAllWithDetails()
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.client', 'c')
            ->leftJoin('o.orderItems', 'oi')
            ->leftJoin('oi.dish', 'd')
            ->addSelect('c')
            ->addSelect('oi')
            ->addSelect('d')
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}