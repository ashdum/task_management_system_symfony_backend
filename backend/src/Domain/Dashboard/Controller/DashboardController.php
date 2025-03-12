<?php

namespace App\Domain\Dashboard\Controller;

use App\Domain\Dashboard\DTO\CreateDashboardDTO;
use App\Domain\Dashboard\DTO\UpdateDashboardDTO;
use App\Domain\Dashboard\Entity\Dashboard;
use App\Domain\Dashboard\Service\DashboardService;
use App\Domain\User\Entity\User;
use App\Shared\Controller\BaseController;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/dashboards', name: 'api_dashboards_')]
class DashboardController extends BaseController
{
    private DashboardService $dashboardService;
    private Security $security;

    public function __construct(
        DashboardService $dashboardService,
        Security $security,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        parent::__construct($serializer, $validator);
        $this->dashboardService = $dashboardService;
        $this->security = $security;
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/dashboards',
        summary: 'Создать новый дашборд',
        tags: ['Dashboards'],
        security: [['JWT-auth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateDashboardDTO::class))
        ),
        responses: [
            new OA\Response(response: 201, description: 'Дашборд создан', content: new OA\JsonContent(ref: new Model(type: Dashboard::class, groups: ['dashboard:read']))),
            new OA\Response(response: 400, description: 'Неверный формат запроса'),
            new OA\Response(response: 401, description: 'Не авторизован')
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, CreateDashboardDTO::class);
        /** @var User|null $user */
        $user = $this->security->getUser();
        if (!$user) {
            throw new UnauthorizedHttpException('jwt', 'Пользователь не авторизован');
        }
        $dashboard = $this->dashboardService->create($dto, $user->getId());
        return $this->jsonResponse($dashboard, 201, ['dashboard:read']);
    }

    #[Route('', name: 'find_all', methods: ['GET'])]
    #[OA\Get(
        path: '/api/dashboards',
        summary: 'Получить дашборды текущего пользователя',
        tags: ['Dashboards'],
        security: [['JWT-auth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Список дашбордов', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: Dashboard::class, groups: ['dashboard:read'])))),
            new OA\Response(response: 401, description: 'Не авторизован')
        ]
    )]
    public function findAll(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        if (!$user) {
            throw new UnauthorizedHttpException('jwt', 'Пользователь не авторизован');
        }
        $dashboards = $this->dashboardService->findAll($user->getId());
        return $this->jsonResponse($dashboards, 200, ['dashboard:read']);
    }

    #[Route('/{id}', name: 'find_one', methods: ['GET'])]
    #[OA\Get(
        path: '/api/dashboards/{id}',
        summary: 'Получить дашборд по ID',
        tags: ['Dashboards'],
        security: [['JWT-auth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Детали дашборда', content: new OA\JsonContent(ref: new Model(type: Dashboard::class, groups: ['dashboard:read']))),
            new OA\Response(response: 401, description: 'Не авторизован'),
            new OA\Response(response: 404, description: 'Дашборд не найден')
        ]
    )]
    public function findOne(string $id): JsonResponse
    {
        $dashboard = $this->dashboardService->findOne($id);
        return $this->jsonResponse($dashboard, 200, ['dashboard:read']);
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'])]
    #[OA\Patch(
        path: '/api/dashboards/{id}',
        summary: 'Обновить дашборд по ID',
        tags: ['Dashboards'],
        security: [['JWT-auth' => []]],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: new Model(type: UpdateDashboardDTO::class))),
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Дашборд обновлен', content: new OA\JsonContent(ref: new Model(type: Dashboard::class, groups: ['dashboard:read']))),
            new OA\Response(response: 401, description: 'Не авторизован'),
            new OA\Response(response: 404, description: 'Дашборд не найден')
        ]
    )]
    public function update(string $id, Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, UpdateDashboardDTO::class);
        $dashboard = $this->dashboardService->update($id, $dto);
        return $this->jsonResponse($dashboard, 200, ['dashboard:read']);
    }

    #[Route('/{id}', name: 'remove', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/dashboards/{id}',
        summary: 'Удалить дашборд по ID (только для администратора)',
        tags: ['Dashboards'],
        security: [['JWT-auth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Дашборд удален'),
            new OA\Response(response: 401, description: 'Не авторизован'),
            new OA\Response(response: 403, description: 'Только администратор может удалить дашборд'),
            new OA\Response(response: 404, description: 'Дашборд не найден')
        ]
    )]
    public function remove(string $id): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        if (!$user) {
            throw new UnauthorizedHttpException('jwt', 'Пользователь не авторизован');
        }
        $this->dashboardService->remove($id, $user->getId());
        return $this->jsonResponse(['message' => 'Дашборд удален'], 200);
    }
}