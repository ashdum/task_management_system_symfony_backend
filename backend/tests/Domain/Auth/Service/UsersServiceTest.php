<?php
namespace App\Tests\Domain\User\Service;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepository;
use App\Domain\User\Service\UsersService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsersServiceTest extends TestCase
{
    private $userRepository;
    private $passwordHasher;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
    }

    public function testCreateUser(): void
    {
        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->userRepository->expects($this->once())
            ->method('save');

        $service = new UsersService($this->userRepository, $this->passwordHasher);
        $user = $service->createUser('test@example.com', 'password', 'Test User');

        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('hashed_password', $user->getPassword());
    }
}