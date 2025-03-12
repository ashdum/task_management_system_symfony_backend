<?php

namespace App\Tests\Domain\Dashboard\Service;

use App\Domain\Dashboard\DTO\CreateDashboardDTO;
use App\Domain\Dashboard\DTO\UpdateDashboardDTO;
use App\Domain\Dashboard\Entity\Dashboard;
use App\Domain\Dashboard\Entity\DashboardUser;
use App\Domain\Dashboard\Repository\DashboardRepository;
use App\Domain\Dashboard\Repository\DashboardUserRepository;
use App\Domain\Dashboard\Service\DashboardService;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepository;
use App\Shared\Enum\DelStatusEnum;
use App\Shared\Enum\RoleEnum;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DashboardServiceTest extends TestCase
{
    private DashboardRepository $dashboardRepository;
    private DashboardUserRepository $dashboardUserRepository;
    private UserRepository $userRepository;
    private DashboardService $dashboardService;

    protected function setUp(): void
    {
        $this->dashboardRepository = $this->createMock(DashboardRepository::class);
        $this->dashboardUserRepository = $this->createMock(DashboardUserRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->dashboardService = new DashboardService(
            $this->dashboardRepository,
            $this->dashboardUserRepository,
            $this->userRepository
        );
    }

    private function createUser(string $id = 'user-id-123'): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        return $user;
    }

    private function createDashboard(string $id = 'dashboard-id-123'): Dashboard
    {
        $dashboard = new Dashboard();
        $reflection = new \ReflectionClass($dashboard);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($dashboard, $id);
        $dashboard->setTitle('Test Dashboard');
        return $dashboard;
    }

    public function testCreateSuccess(): void
    {
        $dto = new CreateDashboardDTO();
        $dto->setTitle('Test Dashboard');
        $dto->setOwnerIds(['user-id-123']);
        $user = $this->createUser();
        $dashboard = $this->createDashboard();

        $this->userRepository->expects($this->once())
            ->method('getActiveById')
            ->with('user-id-123')
            ->willReturn($user);

        $this->dashboardRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Dashboard $d) use ($dto) {
                return $d->getTitle() === $dto->getTitle() &&
                       $d->getOwnerIds() === $dto->getOwnerIds();
            }));

        $this->dashboardUserRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (DashboardUser $du) use ($user) {
                return $du->getUser() === $user &&
                       $du->getRole() === RoleEnum::ADMIN;
            }));

        $result = $this->dashboardService->create($dto, 'user-id-123');

        $this->assertInstanceOf(Dashboard::class, $result);
        $this->assertEquals('Test Dashboard', $result->getTitle());
    }

    public function testCreateUserNotFound(): void
    {
        $dto = new CreateDashboardDTO();
        $dto->setTitle('Test Dashboard');

        $this->userRepository->expects($this->once())
            ->method('getActiveById')
            ->with('user-id-123')
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Пользователь с ID user-id-123 не найден');

        $this->dashboardService->create($dto, 'user-id-123');
    }

    public function testFindOneSuccess(): void
    {
        $dashboardId = 'dashboard-id-123';
        $dashboard = $this->createDashboard($dashboardId);

        $this->dashboardRepository->expects($this->once())
            ->method('findActiveById')
            ->with($dashboardId)
            ->willReturn($dashboard);

        $result = $this->dashboardService->findOne($dashboardId);

        $this->assertInstanceOf(Dashboard::class, $result);
        $this->assertEquals($dashboardId, $result->getId());
        $this->assertEquals('Test Dashboard', $result->getTitle());
    }

    public function testFindOneNotFound(): void
    {
        $dashboardId = 'nonexistent-id';

        $this->dashboardRepository->expects($this->once())
            ->method('findActiveById')
            ->with($dashboardId)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage("Дашборд с ID {$dashboardId} не найден");

        $this->dashboardService->findOne($dashboardId);
    }

    public function testFindAllSuccess(): void
    {
        $userId = 'user-id-123';
        $dashboard = $this->createDashboard();
        $dashboards = [$dashboard];

        $this->dashboardRepository->expects($this->once())
            ->method('findAllByUserId')
            ->with($userId)
            ->willReturn($dashboards);

        $result = $this->dashboardService->findAll($userId);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals($dashboard, $result[0]);
    }

    public function testUpdateSuccess(): void
    {
        $dashboardId = 'dashboard-id-123';
        $dashboard = $this->createDashboard($dashboardId);
        $dto = new UpdateDashboardDTO();
        $dto->setTitle('Updated Dashboard');

        $this->dashboardRepository->expects($this->once())
            ->method('findActiveById')
            ->with($dashboardId)
            ->willReturn($dashboard);

        $this->dashboardRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Dashboard $d) use ($dto) {
                return $d->getTitle() === $dto->getTitle();
            }));

        $result = $this->dashboardService->update($dashboardId, $dto);

        $this->assertInstanceOf(Dashboard::class, $result);
        $this->assertEquals('Updated Dashboard', $result->getTitle());
    }

    public function testRemoveSuccess(): void
    {
        $dashboardId = 'dashboard-id-123';
        $userId = 'user-id-123';
        $user = $this->createUser($userId);
        $dashboard = $this->createDashboard($dashboardId);
        $dashboardUser = new DashboardUser(Uuid::uuid4()->toString(), $user, $dashboard, RoleEnum::ADMIN);

        $dashboard->addDashboardUser($dashboardUser);

        $this->dashboardRepository->expects($this->once())
            ->method('findActiveById')
            ->with($dashboardId)
            ->willReturn($dashboard);

        $this->dashboardUserRepository->expects($this->once())
            ->method('deleteByDashboardId')
            ->with($dashboardId);

        $this->dashboardRepository->expects($this->once())
            ->method('remove')
            ->with($dashboard);

        $this->dashboardService->remove($dashboardId, $userId);
    }

    public function testRemoveAccessDenied(): void
    {
        $dashboardId = 'dashboard-id-123';
        $userId = 'user-id-123';
        $dashboard = $this->createDashboard($dashboardId);

        $this->dashboardRepository->expects($this->once())
            ->method('findActiveById')
            ->with($dashboardId)
            ->willReturn($dashboard);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Только администратор может удалить дашборд');

        $this->dashboardService->remove($dashboardId, $userId);
    }

    public function testUpdateNotFound(): void
    {
        $dashboardId = 'nonexistent-id';
        $dto = new UpdateDashboardDTO();

        $this->dashboardRepository->expects($this->once())
            ->method('findActiveById')
            ->with($dashboardId)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage("Дашборд с ID {$dashboardId} не найден");

        $this->dashboardService->update($dashboardId, $dto);
    }

    public function testFindAllEmpty(): void
    {
        $userId = 'user-id-123';

        $this->dashboardRepository->expects($this->once())
            ->method('findAllByUserId')
            ->with($userId)
            ->willReturn([]);

        $result = $this->dashboardService->findAll($userId);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}