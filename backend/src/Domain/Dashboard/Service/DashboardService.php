<?php

namespace App\Domain\Dashboard\Service;

use App\Domain\Dashboard\DTO\CreateDashboardDTO;
use App\Domain\Dashboard\DTO\UpdateDashboardDTO;
use App\Domain\Dashboard\Entity\Dashboard;
use App\Domain\Dashboard\Entity\DashboardUser;
use App\Domain\Dashboard\Repository\DashboardRepository;
use App\Domain\Dashboard\Repository\DashboardUserRepository;
use App\Domain\User\Repository\UserRepository;
use App\Shared\Enum\RoleEnum;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DashboardService
{
    private DashboardRepository $dashboardRepository;
    private DashboardUserRepository $dashboardUserRepository;
    private UserRepository $userRepository;

    public function __construct(
        DashboardRepository $dashboardRepository,
        DashboardUserRepository $dashboardUserRepository,
        UserRepository $userRepository
    ) {
        $this->dashboardRepository = $dashboardRepository;
        $this->dashboardUserRepository = $dashboardUserRepository;
        $this->userRepository = $userRepository;
    }

    public function create(CreateDashboardDTO $dto, string $userId): Dashboard
    {
        $user = $this->userRepository->getActiveById($userId);
        if (!$user) {
            throw new NotFoundHttpException("Пользователь с ID {$userId} не найден");
        }

        $dashboard = new Dashboard();
        $dashboard->setTitle($dto->getTitle())
            ->setOwnerIds($dto->getOwnerIds())
            ->setBackground($dto->getBackground())
            ->setDescription($dto->getDescription())
            ->setIsPublic($dto->isPublic() ?? false)
            ->setSettings($dto->getSettings());

        $this->dashboardRepository->save($dashboard);

        $dashboardUser = new DashboardUser(
            Uuid::uuid4()->toString(),
            $user,
            $dashboard,
            RoleEnum::ADMIN
        );
        $this->dashboardUserRepository->save($dashboardUser);

        return $dashboard;
    }

    public function findAll(string $userId): array
    {
        return $this->dashboardRepository->findAllByUserId($userId);
    }

    public function findOne(string $id): Dashboard
    {
        $dashboard = $this->dashboardRepository->findActiveById($id);
        if (!$dashboard) {
            throw new NotFoundHttpException("Дашборд с ID {$id} не найден");
        }
        return $dashboard;
    }

    public function update(string $id, UpdateDashboardDTO $dto): Dashboard
    {
        $dashboard = $this->findOne($id);
        if ($dto->getTitle() !== null) $dashboard->setTitle($dto->getTitle());
        if ($dto->getOwnerIds() !== null) $dashboard->setOwnerIds($dto->getOwnerIds());
        if ($dto->getBackground() !== null) $dashboard->setBackground($dto->getBackground());
        if ($dto->getDescription() !== null) $dashboard->setDescription($dto->getDescription());
        if ($dto->getIsPublic() !== null) $dashboard->setIsPublic($dto->getIsPublic());
        if ($dto->getSettings() !== null) $dashboard->setSettings($dto->getSettings());

        $this->dashboardRepository->save($dashboard);
        return $dashboard;
    }

    public function remove(string $id, string $userId): void
    {
        $dashboard = $this->findOne($id);
        $dashboardUser = $dashboard->getDashboardUsers()->filter(
            fn(DashboardUser $du) => $du->getUser()->getId() === $userId && $du->getRole() === RoleEnum::ADMIN
        )->first();

        if (!$dashboardUser) {
            throw new AccessDeniedHttpException('Только администратор может удалить дашборд');
        }

        $this->dashboardUserRepository->deleteByDashboardId($id);
        $this->dashboardRepository->remove($dashboard);
    }
}