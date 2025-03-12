<?php

namespace App\Tests\Domain\Dashboard\Repository;

use App\Domain\Dashboard\Entity\Dashboard;
use App\Domain\Dashboard\Repository\DashboardRepository;
use App\Shared\Enum\DelStatusEnum;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DashboardRepositoryTest extends TestCase
{
    private DashboardRepository|MockObject $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(DashboardRepository::class);
    }

    private function createDashboard(): Dashboard
    {
        $dashboard = new Dashboard();
        $reflection = new \ReflectionClass($dashboard);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($dashboard, 'dashboard-id-123');
        $dashboard->setTitle('Test Dashboard');
        return $dashboard;
    }

    public function testFindActiveByIdSuccess(): void
    {
        $dashboardId = 'dashboard-id-123';
        $dashboard = $this->createDashboard();

        $this->repository->expects($this->once())
            ->method('findActiveById')
            ->with($dashboardId)
            ->willReturn($dashboard);

        $result = $this->repository->findActiveById($dashboardId);

        $this->assertEquals($dashboard, $result);
    }

    public function testFindAllByUserIdSuccess(): void
    {
        $userId = 'user-id-123';
        $dashboard = $this->createDashboard();
        $dashboards = [$dashboard];

        $this->repository->expects($this->once())
            ->method('findAllByUserId')
            ->with($userId)
            ->willReturn($dashboards);

        $result = $this->repository->findAllByUserId($userId);

        $this->assertEquals($dashboards, $result);
    }

    public function testSave(): void
    {
        $dashboard = $this->createDashboard();

        $this->repository->expects($this->once())
            ->method('save')
            ->with($dashboard);

        $this->repository->save($dashboard);
    }

    public function testRemove(): void
    {
        $dashboard = $this->createDashboard();

        $this->repository->expects($this->once())
            ->method('remove')
            ->with($dashboard);

        $this->repository->remove($dashboard);
    }
}