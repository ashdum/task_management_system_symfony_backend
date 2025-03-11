<?php

namespace App\Tests\Domain\Auth\Service;

use App\Domain\Auth\DTO\LoginDTO;
use App\Domain\Auth\DTO\RegisterDTO;
use App\Domain\Auth\Service\AuthService;
use App\Domain\Auth\Service\OAuthService;
use App\Domain\Auth\Service\TokenService;
use App\Domain\User\Entity\User;
use App\Shared\Enum\DelStatusEnum;
use App\Shared\Enum\RoleEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Domain\User\Service\UsersService;

class AuthServiceTest extends TestCase
{
    private $usersService;
    private $passwordHasher;
    private $tokenService;
    private $oauthService;
    private $authService;

    protected function setUp(): void
    {
        $this->usersService = $this->createMock(UsersService::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->tokenService = $this->createMock(TokenService::class);
        $this->oauthService = $this->createMock(OAuthService::class);

        $this->authService = new AuthService(
            $this->usersService,
            $this->passwordHasher,
            $this->tokenService,
            $this->oauthService
        );
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('test@example.com')
            ->setPassword('hashed_password')
            ->setFullName('Test User')
            ->setRole(RoleEnum::USER)
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime())
            ->setDelStatus(DelStatusEnum::ACTIVE);
        return $user;
    }

    public function testRegisterSuccess(): void
    {
        $dto = new RegisterDTO();
        $dto->setEmail('test@example.com');
        $dto->setPassword('StrongPass123!');
        $dto->setFullName('Test User');

        $user = $this->createUser();

        $this->usersService->expects($this->once())
            ->method('getUserByEmail')
            ->with($dto->getEmail())
            ->willReturn(null);

        $this->usersService->expects($this->once())
            ->method('createUser')
            ->with($dto->getEmail(), $dto->getPassword(), $dto->getFullName(), null)
            ->willReturn($user);

        $this->tokenService->expects($this->once())
            ->method('generateTokens')
            ->with($user)
            ->willReturn([
                'accessToken' => 'access_token',
                'refreshToken' => 'refresh_token',
                'user' => ['email' => 'test@example.com']
            ]);

        $result = $this->authService->register($dto);

        $this->assertEquals('access_token', $result['accessToken']);
        $this->assertEquals('refresh_token', $result['refreshToken']);
        $this->assertEquals('test@example.com', $result['user']['email']);
    }

    public function testRegisterUserExists(): void
    {
        $dto = new RegisterDTO();
        $dto->setEmail('test@example.com');

        $this->usersService->expects($this->once())
            ->method('getUserByEmail')
            ->with($dto->getEmail())
            ->willReturn($this->createUser());

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Пользователь с таким email уже существует');

        $this->authService->register($dto);
    }

    public function testLoginSuccess(): void
    {
        $dto = new LoginDTO();
        $dto->setEmail('test@example.com');
        $dto->setPassword('StrongPass123!');

        $user = $this->createUser();

        $this->usersService->expects($this->once())
            ->method('getUserByEmail')
            ->with($dto->getEmail())
            ->willReturn($user);

        $this->passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, $dto->getPassword())
            ->willReturn(true);

        $this->tokenService->expects($this->once())
            ->method('generateTokens')
            ->with($user)
            ->willReturn(['accessToken' => 'access_token', 'refreshToken' => 'refresh_token']);

        $result = $this->authService->login($dto);

        $this->assertEquals('access_token', $result['accessToken']);
        $this->assertEquals('refresh_token', $result['refreshToken']);
    }

    public function testLoginInvalidCredentials(): void
    {
        $dto = new LoginDTO();
        $dto->setEmail('test@example.com');
        $dto->setPassword('WrongPass');

        $user = $this->createUser();

        $this->usersService->expects($this->once())
            ->method('getUserByEmail')
            ->with($dto->getEmail())
            ->willReturn($user);

        $this->passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, $dto->getPassword())
            ->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Неверные учетные данные');

        $this->authService->login($dto);
    }

    public function testRefreshSuccess(): void
    {
        $refreshToken = 'valid_refresh_token';
        $expectedResult = [
            'accessToken' => 'new_access_token',
            'refreshToken' => 'new_refresh_token',
            'user' => ['email' => 'test@example.com']
        ];

        $this->tokenService->expects($this->once())
            ->method('refresh')
            ->with($refreshToken)
            ->willReturn($expectedResult);

        $result = $this->authService->refresh($refreshToken);

        $this->assertEquals('new_access_token', $result['accessToken']);
        $this->assertEquals('new_refresh_token', $result['refreshToken']);
    }

    public function testRefreshInvalidToken(): void
    {
        $this->tokenService->expects($this->once())
            ->method('refresh')
            ->with('invalid_token')
            ->willThrowException(new UnauthorizedHttpException('jwt', 'Refresh-токен недействителен или истёк'));

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Refresh-токен недействителен или истёк');

        $this->authService->refresh('invalid_token');
    }

    public function testChangePasswordSuccess(): void
    {
        $user = $this->createUser();
        $currentPassword = 'StrongPass123!';
        $newPassword = 'NewPass123!';

        $this->passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, $currentPassword)
            ->willReturn(true);

        $this->usersService->expects($this->once())
            ->method('changePassword')
            ->with($user, $newPassword);

        $this->tokenService->expects($this->once())
            ->method('logout')
            ->with($user);

        $this->authService->changePassword($user, $currentPassword, $newPassword);
    }

    public function testChangePasswordInvalidCurrentPassword(): void
    {
        $user = $this->createUser();

        $this->passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, 'wrong_password')
            ->willReturn(false);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Текущий пароль неверный');

        $this->authService->changePassword($user, 'wrong_password', 'NewPass123!');
    }

    public function testGoogleLoginSuccess(): void
    {
        $credential = 'google_credential';
        $user = $this->createUser();

        $this->oauthService->expects($this->once())
            ->method('googleLogin')
            ->with($credential)
            ->willReturn($user);

        $this->tokenService->expects($this->once())
            ->method('generateTokens')
            ->with($user)
            ->willReturn(['accessToken' => 'access_token', 'refreshToken' => 'refresh_token']);

        $result = $this->authService->googleLogin($credential);

        $this->assertEquals('access_token', $result['accessToken']);
    }

    public function testGoogleLoginInvalidToken(): void
    {
        $credential = 'invalid_credential';

        $this->oauthService->expects($this->once())
            ->method('googleLogin')
            ->with($credential)
            ->willThrowException(new UnauthorizedHttpException('google', 'Неверный Google токен'));

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Неверный Google токен');

        $this->authService->googleLogin($credential);
    }

    public function testGithubLoginSuccess(): void
    {
        $code = 'github_code';
        $user = $this->createUser();

        $this->oauthService->expects($this->once())
            ->method('githubLogin')
            ->with($code)
            ->willReturn($user);

        $this->tokenService->expects($this->once())
            ->method('generateTokens')
            ->with($user)
            ->willReturn(['accessToken' => 'access_token', 'refreshToken' => 'refresh_token']);

        $result = $this->authService->githubLogin($code);

        $this->assertEquals('access_token', $result['accessToken']);
    }

    public function testLogoutSuccess(): void
    {
        $user = $this->createUser();

        $this->tokenService->expects($this->once())
            ->method('logout')
            ->with($user);

        $this->authService->logout($user);
    }

    public function testGetUserByJwtTokenSuccess(): void
    {
        $jwtToken = 'valid_jwt';
        $user = $this->createUser();

        $this->tokenService->expects($this->once())
            ->method('getUserByJwtToken')
            ->with($jwtToken)
            ->willReturn($user);

        $result = $this->authService->getUserByJwtToken($jwtToken);

        $this->assertEquals($user, $result);
    }

    public function testGetUserByJwtTokenInvalidToken(): void
    {
        $jwtToken = 'invalid_jwt';

        $this->tokenService->expects($this->once())
            ->method('getUserByJwtToken')
            ->with($jwtToken)
            ->willThrowException(new UnauthorizedHttpException('jwt', 'Токен недействителен или был отозван'));

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Токен недействителен или был отозван');

        $this->authService->getUserByJwtToken($jwtToken);
    }
}