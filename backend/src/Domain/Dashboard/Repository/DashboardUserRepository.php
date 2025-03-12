<?php

namespace App\Domain\Dashboard\Repository;

use App\Domain\Dashboard\Entity\DashboardUser;
use App\Shared\Enum\DelStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DashboardUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DashboardUser::class);
    }

    public function save(DashboardUser $dashboardUser): void
    {
        $this->getEntityManager()->persist($dashboardUser);
        $this->getEntityManager()->flush();
    }

    public function deleteByDashboardId(string $dashboardId): void
    {
        $this->createQueryBuilder('du')
            ->delete()
            ->where('du.dashboard = :dashboardId')
            ->setParameter('dashboardId', $dashboardId)
            ->getQuery()
            ->execute();
    }
}