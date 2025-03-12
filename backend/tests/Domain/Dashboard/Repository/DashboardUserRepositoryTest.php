<?php

namespace App\Tests\Domain\Dashboard\Repository;

use App\Domain\Dashboard\Entity\Dashboard;
use App\Domain\Dashboard\Entity\DashboardUser;
use App\Domain\Dashboard\Repository\DashboardUserRepository;
use App\Domain\User\Entity\User;
use App\Shared\Enum\RoleEnum;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class DashboardUserRepositoryTest extends TestCase
{
    private DashboardUserRepository|MockObject $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(DashboardUserRepository::class);
    }

    private function createDashboardUser(): DashboardUser
    {
        $user = $this->createMock(User::class);
        $dashboard = new Dashboard();
        return new DashboardUser(Uuid::uuid4()->toString(), $user, $dashboard, RoleEnum::ADMIN);
    }

    public function testSave(): void
    {
        $dashboardUser = $this->createDashboardUser();

        $this->repository->expects($this->once())
            ->method('save')
            ->with($dashboardUser);

        $this->repository->save($dashboardUser);
    }

    public function testDeleteByDashboardId(): void
    {
        $dashboardId = 'dashboard-id-123';

        $this->repository->expects($this->once())
            ->method('deleteByDashboardId')
            ->with($dashboardId);

        $this->repository->deleteByDashboardId($dashboardId);
    }
}