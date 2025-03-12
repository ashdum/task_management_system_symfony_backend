<?php

namespace App\Domain\Dashboard\Repository;

use App\Domain\Dashboard\Entity\Dashboard;
use App\Shared\Enum\DelStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DashboardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dashboard::class);
    }

    public function findActiveById(string $id): ?Dashboard
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.id = :id')
            ->andWhere('d.delStatus = :active')
            ->setParameter('id', $id)
            ->setParameter('active', DelStatusEnum::ACTIVE)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllByUserId(string $userId): array
    {
        return $this->createQueryBuilder('d')
            ->innerJoin('d.dashboardUsers', 'du')
            ->andWhere('du.user = :userId')
            ->andWhere('du.delStatus = :active')
            ->andWhere('d.delStatus = :active')
            ->setParameter('userId', $userId)
            ->setParameter('active', DelStatusEnum::ACTIVE)
            ->getQuery()
            ->getResult();
    }

    public function save(Dashboard $dashboard): void
    {
        $this->getEntityManager()->persist($dashboard);
        $this->getEntityManager()->flush();
    }

    public function remove(Dashboard $dashboard): void
    {
        $this->getEntityManager()->remove($dashboard);
        $this->getEntityManager()->flush();
    }
}