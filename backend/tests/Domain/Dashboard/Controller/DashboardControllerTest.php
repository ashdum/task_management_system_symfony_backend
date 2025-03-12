<?php

namespace App\Tests\Domain\Dashboard\Controller;

use App\Domain\Dashboard\Controller\DashboardController;
use App\Domain\Dashboard\DTO\CreateDashboardDTO;
use App\Domain\Dashboard\DTO\UpdateDashboardDTO;
use App\Domain\Dashboard\Entity\Dashboard;
use App\Domain\Dashboard\Service\DashboardService;
use App\Domain\User\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DashboardControllerTest extends TestCase
{
    private DashboardController $controller;
    private DashboardService|MockObject $dashboardService;
    private Security|MockObject $security;
    private SerializerInterface|MockObject $serializer;
    private ValidatorInterface|MockObject $validator;

    protected function setUp(): void
    {
        $this->dashboardService = $this->createMock(DashboardService::class);
        $this->security = $this->createMock(Security::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->controller = new DashboardController(
            $this->dashboardService,
            $this->security,
            $this->serializer,
            $this->validator
        );
    }

    private function createUser(): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn('user-id-123');
        return $user;
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

    public function testCreateSuccess(): void
    {
        $dto = new CreateDashboardDTO();
        $dto->setTitle('Test Dashboard');
        $user = $this->createUser();
        $dashboard = $this->createDashboard();
        $request = new Request([], [], [], [], [], [], json_encode(['title' => 'Test Dashboard']));

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with($request->getContent(), CreateDashboardDTO::class, 'json')
            ->willReturn($dto);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($dto)
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->dashboardService->expects($this->once())
            ->method('create')
            ->with($dto, 'user-id-123')
            ->willReturn($dashboard);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($dashboard, 'json', ['groups' => ['dashboard:read']])
            ->willReturn('serialized_dashboard');

        $response = $this->controller->create($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('serialized_dashboard', $response->getContent());
    }

    public function testCreateUnauthorized(): void
    {
        // Добавляем минимальное содержимое, чтобы избежать TypeError
        $request = new Request([], [], [], [], [], [], '{}');

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with('{}', CreateDashboardDTO::class, 'json')
            ->willReturn(new CreateDashboardDTO());

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Пользователь не авторизован');

        $this->controller->create($request);
    }

    public function testFindAllSuccess(): void
    {
        $user = $this->createUser();
        $dashboard = $this->createDashboard();
        $dashboards = [$dashboard];

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->dashboardService->expects($this->once())
            ->method('findAll')
            ->with('user-id-123')
            ->willReturn($dashboards);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($dashboards, 'json', ['groups' => ['dashboard:read']])
            ->willReturn('serialized_dashboards');

        $response = $this->controller->findAll();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('serialized_dashboards', $response->getContent());
    }

    public function testFindOneSuccess(): void
    {
        $dashboardId = 'dashboard-id-123';
        $dashboard = $this->createDashboard();

        $this->dashboardService->expects($this->once())
            ->method('findOne')
            ->with($dashboardId)
            ->willReturn($dashboard);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($dashboard, 'json', ['groups' => ['dashboard:read']])
            ->willReturn('serialized_dashboard');

        $response = $this->controller->findOne($dashboardId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('serialized_dashboard', $response->getContent());
    }

    public function testUpdateSuccess(): void
    {
        $dashboardId = 'dashboard-id-123';
        $dto = new UpdateDashboardDTO();
        $dto->setTitle('Updated Dashboard');
        $dashboard = $this->createDashboard();
        $request = new Request([], [], [], [], [], [], json_encode(['title' => 'Updated Dashboard']));

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with($request->getContent(), UpdateDashboardDTO::class, 'json')
            ->willReturn($dto);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($dto)
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->dashboardService->expects($this->once())
            ->method('update')
            ->with($dashboardId, $dto)
            ->willReturn($dashboard);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($dashboard, 'json', ['groups' => ['dashboard:read']])
            ->willReturn('serialized_dashboard');

        $response = $this->controller->update($dashboardId, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('serialized_dashboard', $response->getContent());
    }

    public function testRemoveSuccess(): void
    {
        $dashboardId = 'dashboard-id-123';
        $user = $this->createUser();

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->dashboardService->expects($this->once())
            ->method('remove')
            ->with($dashboardId, 'user-id-123');

        $response = $this->controller->remove($dashboardId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            '{"message":"Дашборд удален"}',
            $response->getContent()
        );
    }
}