<?php

namespace App\Tests\Domain\User\Repository;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepository;
use App\Shared\Enum\DelStatusEnum;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserRepositoryTest extends TestCase
{
    private UserRepository|MockObject $userRepository;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
    }

    private function createUser(string $id = 'user-id-123'): User
    {
        $user = new User();
        $reflection = new \ReflectionClass($user);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($user, $id);
        $user->setEmail('test@example.com')
            ->setDelStatus(DelStatusEnum::ACTIVE);
        return $user;
    }

    public function testGetActiveByIdWhenUserExists(): void
    {
        $userId = 'user-id-123';
        $user = $this->createUser($userId);

        $this->userRepository->expects($this->once())
            ->method('getActiveById')
            ->with($userId)
            ->willReturn($user);

        $result = $this->userRepository->getActiveById($userId);

        $this->assertSame($user, $result);
        $this->assertEquals($userId, $result->getId());
        $this->assertEquals(DelStatusEnum::ACTIVE, $result->getDelStatus());
    }

    public function testGetActiveByIdWhenUserDoesNotExist(): void
    {
        $userId = 'nonexistent-id';

        $this->userRepository->expects($this->once())
            ->method('getActiveById')
            ->with($userId)
            ->willReturn(null);

        $result = $this->userRepository->getActiveById($userId);

        $this->assertNull($result);
    }

    public function testGetActiveByEmailWhenUserExists(): void
    {
        $email = 'test@example.com';
        $user = $this->createUser();

        $this->userRepository->expects($this->once())
            ->method('getActiveByEmail')
            ->with($email)
            ->willReturn($user);

        $result = $this->userRepository->getActiveByEmail($email);

        $this->assertSame($user, $result);
        $this->assertEquals($email, $result->getEmail());
        $this->assertEquals(DelStatusEnum::ACTIVE, $result->getDelStatus());
    }

    public function testGetActiveByEmailWhenUserDoesNotExist(): void
    {
        $email = 'nonexistent@example.com';

        $this->userRepository->expects($this->once())
            ->method('getActiveByEmail')
            ->with($email)
            ->willReturn(null);

        $result = $this->userRepository->getActiveByEmail($email);

        $this->assertNull($result);
    }

    public function testSave(): void
    {
        $user = $this->createUser();

        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($user);

        $this->userRepository->save($user);
    }

    public function testSoftDeleteSuccess(): void
    {
        $user = $this->createUser();

        $this->userRepository->expects($this->once())
            ->method('softDelete')
            ->with($user);

        $this->userRepository->softDelete($user);
    }

    public function testSoftDeleteWhenUserNotManaged(): void
    {
        $user = $this->createUser();

        $this->userRepository->expects($this->once())
            ->method('softDelete')
            ->with($user)
            ->willThrowException(new \LogicException('Пользователь не найден в базе данных'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Пользователь не найден в базе данных');

        $this->userRepository->softDelete($user);
    }
}