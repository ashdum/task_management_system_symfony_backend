<?php

namespace App\Tests\Domain\User\Repository;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepository;
use App\Shared\Enum\DelStatusEnum;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для UserRepository.
 */
class UserRepositoryTest extends TestCase
{
    private $managerRegistry;
    private $entityManager;
    private $queryBuilder;
    private $query;
    private $userRepository;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(AbstractQuery::class);

        // Настройка ManagerRegistry
        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($this->entityManager);

        // Настройка QueryBuilder для возврата Query
        $this->entityManager->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->any())
            ->method('andWhere')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->userRepository = new UserRepository($this->managerRegistry);
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('test@example.com')
            ->setDelStatus(DelStatusEnum::ACTIVE);
        return $user;
    }

    public function testGetActiveByIdWhenUserExists(): void
    {
        $user = $this->createUser();
        $id = $user->getId();

        $this->query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($user);

        $result = $this->userRepository->getActiveById($id);

        $this->assertSame($user, $result);
    }

    public function testGetActiveByIdWhenUserDoesNotExist(): void
    {
        $this->query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $result = $this->userRepository->getActiveById('nonexistent-id');

        $this->assertNull($result);
    }

    public function testGetActiveByEmailWhenUserExists(): void
    {
        $user = $this->createUser();

        $this->query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($user);

        $result = $this->userRepository->getActiveByEmail('test@example.com');

        $this->assertSame($user, $result);
    }

    public function testGetActiveByEmailWhenUserDoesNotExist(): void
    {
        $this->query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $result = $this->userRepository->getActiveByEmail('nonexistent@example.com');

        $this->assertNull($result);
    }

    public function testSave(): void
    {
        $user = $this->createUser();

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->userRepository->save($user);
    }

    public function testSoftDeleteWhenUserExists(): void
    {
        $user = $this->createMock(User::class);

        $this->entityManager->expects($this->once())
            ->method('contains')
            ->with($user)
            ->willReturn(true);

        $user->expects($this->once())
            ->method('softDelete');

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->userRepository->softDelete($user);
    }

    public function testSoftDeleteWhenUserNotManaged(): void
    {
        $user = $this->createUser();

        $this->entityManager->expects($this->once())
            ->method('contains')
            ->with($user)
            ->willReturn(false);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Пользователь не найден в базе данных');

        $this->userRepository->softDelete($user);
    }
}