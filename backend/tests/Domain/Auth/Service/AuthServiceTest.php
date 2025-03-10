<?php

namespace App\Tests\Domain\Auth\Service;

use App\Domain\Auth\DTO\LoginDTO;
use App\Domain\Auth\DTO\RegisterDTO;
use App\Domain\Auth\Service\AuthService;
use App\Domain\User\Entity\User;
use App\Shared\Enum\DelStatusEnum;
use App\Shared\Enum\RoleEnum;
use App\Shared\Service\RedisService;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Google\Client as GoogleClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use App\Domain\User\Service\UsersService;

class AuthServiceTest extends TestCase
{
    private $usersService;
    private $jwtManager;
    private $passwordHasher;
    private $redisService;
    private $jwtEncoder;
    private $googleClient;
    private $httpClient;
    private $authService;

    protected function setUp(): void
    {
        $this->usersService = $this->createMock(UsersService::class);
        $this->jwtManager = $this->createMock(JWTTokenManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->redisService = $this->createMock(RedisService::class);
        $this->jwtEncoder = $this->createMock(JWTEncoderInterface::class);
        $this->googleClient = $this->createMock(GoogleClient::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);

        $this->authService = new AuthService(
            $this->usersService,
            $this->jwtManager,
            $this->passwordHasher,
            $this->redisService,
            $this->jwtEncoder,
            $this->googleClient,
            $this->httpClient,
            'google-client-id',
            'github-client-id',
            'github-client-secret'
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
        $dto->email = 'test@example.com';
        $dto->password = 'StrongPass123!';
        $dto->fullName = 'Test User';

        $user = $this->createUser();

        $this->usersService->expects($this->once())
            ->method('getUserByEmail')
            ->with($dto->email)
            ->willReturn(null);

        $this->usersService->expects($this->once())
            ->method('createUser')
            ->with($dto->email, $dto->password, $dto->fullName, null)
            ->willReturn($user);

        $this->jwtManager->expects($this->once())
            ->method('create')
            ->with($user)
            ->willReturn('access_token');

        $this->jwtEncoder->expects($this->once())
            ->method('encode')
            ->with($this->callback(function ($payload) {
                return isset($payload['sub']) && isset($payload['email']) && isset($payload['exp']);
            }))
            ->willReturn('refresh_token');

        $this->redisService->expects($this->exactly(2))
            ->method('setToken');

        $result = $this->authService->register($dto);

        $this->assertEquals('access_token', $result['accessToken']);
        $this->assertEquals('refresh_token', $result['refreshToken']);
        $this->assertEquals('test@example.com', $result['user']['email']);
    }

    public function testRegisterUserExists(): void
    {
        $dto = new RegisterDTO();
        $dto->email = 'test@example.com';

        $this->usersService->expects($this->once())
            ->method('getUserByEmail')
            ->with($dto->email)
            ->willReturn($this->createUser());

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Пользователь с таким email уже существует');

        $this->authService->register($dto);
    }

    public function testLoginSuccess(): void
    {
        $dto = new LoginDTO();
        $dto->email = 'test@example.com';
        $dto->password = 'StrongPass123!';

        $user = $this->createUser();

        $this->usersService->expects($this->once())
            ->method('getUserByEmail')
            ->with($dto->email)
            ->willReturn($user);

        $this->passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, $dto->password)
            ->willReturn(true);

        $this->jwtManager->expects($this->once())
            ->method('create')
            ->willReturn('access_token');

        $this->jwtEncoder->expects($this->once())
            ->method('encode')
            ->willReturn('refresh_token');

        $this->redisService->expects($this->exactly(2))
            ->method('setToken');

        $result = $this->authService->login($dto);

        $this->assertEquals('access_token', $result['accessToken']);
        $this->assertEquals('refresh_token', $result['refreshToken']);
    }

    public function testLoginInvalidCredentials(): void
    {
        $dto = new LoginDTO();
        $dto->email = 'test@example.com';
        $dto->password = 'WrongPass';

        $user = $this->createUser();

        $this->usersService->expects($this->once())
            ->method('getUserByEmail')
            ->with($dto->email)
            ->willReturn($user);

        $this->passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, $dto->password)
            ->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Неверные учетные данные');

        $this->authService->login($dto);
    }

    public function testRefreshSuccess(): void
    {
        $refreshToken = 'valid_refresh_token';
        $user = $this->createUser();
        $userId = $user->getId();

        $this->jwtEncoder->expects($this->once())
            ->method('decode')
            ->with($refreshToken)
            ->willReturn(['sub' => $userId, 'email' => 'test@example.com']);

        $this->redisService->expects($this->once())
            ->method('getToken')
            ->with("refresh_token:{$userId}")
            ->willReturn($refreshToken);

        $this->usersService->expects($this->once())
            ->method('getUser')
            ->with($userId)
            ->willReturn($user);

        $this->jwtManager->expects($this->once())
            ->method('create')
            ->willReturn('new_access_token');

        $this->jwtEncoder->expects($this->once())
            ->method('encode')
            ->willReturn('new_refresh_token');

        $this->redisService->expects($this->exactly(2))
            ->method('setToken');

        $result = $this->authService->refresh($refreshToken);

        $this->assertEquals('new_access_token', $result['accessToken']);
        $this->assertEquals('new_refresh_token', $result['refreshToken']);
    }

    public function testRefreshInvalidToken(): void
    {
        $this->jwtEncoder->expects($this->once())
            ->method('decode')
            ->with('invalid_token')
            ->willThrowException(new JWTDecodeFailureException('reason', 'msg'));

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Ошибка валидации refresh-токена');

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

        $this->redisService->expects($this->exactly(2))
            ->method('deleteToken');

        $this->authService->changePassword($user, $currentPassword, $newPassword);

        $this->assertTrue(true); // Просто проверка, что метод завершился без исключений
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

    public function testGoogleLoginInvalidToken(): void
    {
        $credential = 'invalid_credential';

        $this->googleClient->expects($this->once())
            ->method('verifyIdToken')
            ->with($credential, 'google-client-id') // Добавлен audience
            ->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Неверный Google токен');

        $this->authService->googleLogin($credential);
    }

    public function testGoogleLoginSuccess(): void
    {
        $credential = 'google_credential';
        $user = $this->createUser();

        $this->googleClient->expects($this->once())
            ->method('verifyIdToken')
            ->with($credential, 'google-client-id') // Исправлено на два аргумента
            ->willReturn([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'sub' => 'google_id',
                'picture' => 'avatar_url'
            ]);

        $this->usersService->expects($this->once())
            ->method('getUserByEmail')
            ->with('test@example.com')
            ->willReturn($user);

        $this->usersService->expects($this->once())
            ->method('saveUser')
            ->with($this->callback(function ($savedUser) {
                return $savedUser->getProvider() === 'google' && $savedUser->getProviderId() === 'google_id';
            }));

        $this->jwtManager->expects($this->once())
            ->method('create')
            ->willReturn('access_token');

        $this->jwtEncoder->expects($this->once())
            ->method('encode')
            ->willReturn('refresh_token');

        $result = $this->authService->googleLogin($credential);

        $this->assertEquals('access_token', $result['accessToken']);
    }

    public function testGithubLoginSuccess(): void
    {
        $code = 'github_code';
        $user = $this->createUser();

        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse->expects($this->once())
            ->method('toArray')
            ->willReturn(['access_token' => 'github_token']);

        $userResponse = $this->createMock(ResponseInterface::class);
        $userResponse->expects($this->once())
            ->method('toArray')
            ->willReturn([
                'id' => 'github_id',
                'login' => 'testuser',
                'name' => 'Test User',
                'avatar_url' => 'avatar_url'
            ]);

        $this->httpClient->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($tokenResponse, $userResponse);

        $this->usersService->expects($this->once())
            ->method('getUserByEmail')
            ->with('testuser@github.com')
            ->willReturn($user);

        $this->usersService->expects($this->once())
            ->method('saveUser')
            ->with($this->callback(function ($savedUser) {
                return $savedUser->getProvider() === 'github' && $savedUser->getProviderId() === 'github_id';
            }));

        $this->jwtManager->expects($this->once())
            ->method('create')
            ->willReturn('access_token');

        $this->jwtEncoder->expects($this->once())
            ->method('encode')
            ->willReturn('refresh_token');

        $result = $this->authService->githubLogin($code);

        $this->assertEquals('access_token', $result['accessToken']);
    }

    public function testLogoutSuccess(): void
    {
        $user = $this->createUser();
        $userId = $user->getId();

        $this->redisService->expects($this->exactly(2))
            ->method('deleteToken')
            ->with($this->callback(function ($key) use ($userId) {
                return in_array($key, [
                    "access_token:{$userId}",
                    "refresh_token:{$userId}"
                ]);
            }));

        $this->authService->logout($user);

        $this->assertTrue(true);
    }

    public function testGetUserByJwtTokenSuccess(): void
    {
        $jwtToken = 'valid_jwt';
        $user = $this->createUser();
        $userId = $user->getId();

        $this->jwtEncoder->expects($this->once())
            ->method('decode')
            ->with($jwtToken)
            ->willReturn(['sub' => $userId]);

        $this->usersService->expects($this->once())
            ->method('getUser')
            ->with($userId)
            ->willReturn($user);

        $result = $this->authService->getUserByJwtToken($jwtToken);

        $this->assertEquals($user, $result);
    }

    public function testGetUserByJwtTokenInvalidToken(): void
    {
        $this->jwtEncoder->expects($this->once())
            ->method('decode')
            ->with('invalid_jwt')
            ->willThrowException(new \Exception('Invalid token'));

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Ошибка обработки токена');

        $this->authService->getUserByJwtToken('invalid_jwt');
    }
}