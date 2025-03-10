<?php
namespace App\Domain\Auth\Service;

use App\Domain\Auth\DTO\LoginDTO;
use App\Domain\Auth\DTO\RegisterDTO;
use App\Domain\User\Entity\User;
use App\Shared\Service\RedisService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Google\Client as GoogleClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Domain\User\Service\UsersService;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use UnexpectedValueException;

class AuthService
{
    private UsersService $usersService;
    private JWTTokenManagerInterface $jwtManager;
    private UserPasswordHasherInterface $passwordHasher;
    private RedisService $redisService;
    private JWTEncoderInterface $jwtEncoder;
    private GoogleClient $googleClient;
    private HttpClientInterface $httpClient;
    private string $googleClientId; // Оставляем только для проверки audience
    private string $githubClientId;
    private string $githubClientSecret;

    public function __construct(
        UsersService $usersService,
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $passwordHasher,
        RedisService $redisService,
        JWTEncoderInterface $jwtEncoder,
        GoogleClient $googleClient,
        HttpClientInterface $httpClient,
        string $googleClientId,
        string $githubClientId,
        string $githubClientSecret
    ) {
        $this->usersService = $usersService;
        $this->jwtManager = $jwtManager;
        $this->passwordHasher = $passwordHasher;
        $this->redisService = $redisService;
        $this->jwtEncoder = $jwtEncoder;
        $this->googleClient = $googleClient;
        $this->httpClient = $httpClient;
        $this->googleClientId = $googleClientId;
        $this->githubClientId = $githubClientId;
        $this->githubClientSecret = $githubClientSecret;
    }

    public function register(RegisterDTO $dto): array
    {
        $existingUser = $this->usersService->getUserByEmail($dto->email);
        if ($existingUser) {
            throw new BadRequestHttpException('Пользователь с таким email уже существует', null, 409);
        }

        $user = $this->usersService->createUser($dto->email, $dto->password, $dto->fullName, $dto->avatar);
        return $this->generateTokens($user);
    }

    public function login(LoginDTO $dto): array
    {
        $user = $this->usersService->getUserByEmail($dto->email);
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $dto->password)) {
            throw new UnauthorizedHttpException('jwt', 'Неверные учетные данные');
        }

        return $this->generateTokens($user);
    }

    public function refresh(string $refreshToken): array
    {
        try {
            $payload = $this->jwtEncoder->decode($refreshToken);
            $userId = $payload['sub'] ?? null;

            if (!$userId) {
                throw new UnauthorizedHttpException('jwt', 'Неверный refresh-токен');
            }

            $storedRefreshToken = $this->redisService->getToken("refresh_token:{$userId}");
            if (!$storedRefreshToken || $storedRefreshToken !== $refreshToken) {
                throw new UnauthorizedHttpException('jwt', 'Refresh-токен недействителен или истёк');
            }

            $user = $this->usersService->getUser($userId);
            if (!$user || !$user->isActive()) {
                throw new UnauthorizedHttpException('jwt', 'Пользователь не найден или удалён');
            }

            return $this->generateTokens($user);
        } catch (JWTDecodeFailureException $e) {
            throw new UnauthorizedHttpException('jwt', 'Ошибка валидации refresh-токена: ' . $e->getMessage());
        }
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            throw new BadRequestHttpException('Текущий пароль неверный');
        }

        $this->usersService->changePassword($user, $newPassword);
        $this->logout($user);
    }

    public function googleLogin(string $credential): array
    {
        try {
            // Указываем токен и audience явно
            $payload = $this->googleClient->verifyIdToken($credential, $this->googleClientId);

            if ($payload === false) {
                throw new UnauthorizedHttpException('google', 'Неверный Google токен');
            }

            if (!isset($payload['email'])) {
                throw new UnauthorizedHttpException('google', 'Неверный Google токен: email отсутствует');
            }

            return $this->loginWithOAuth([
                'email' => $payload['email'],
                'fullName' => $payload['name'] ?? explode('@', $payload['email'])[0],
                'oauthId' => $payload['sub'],
                'provider' => 'google',
                'avatar' => $payload['picture'] ?? '',
            ]);
        } catch (UnexpectedValueException $e) {
            throw new UnauthorizedHttpException('google', 'Неверный формат Google токена: ' . $e->getMessage());
        } catch (LogicException $e) {
            throw new UnauthorizedHttpException('google', 'Ошибка конфигурации Google клиента: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new UnauthorizedHttpException('google', 'Ошибка обработки Google токена: ' . $e->getMessage());
        }
    }

    public function githubLogin(string $code): array
    {
        try {
            $tokenResponse = $this->httpClient->request('POST', 'https://github.com/login/oauth/access_token', [
                'json' => [
                    'client_id' => $this->githubClientId,
                    'client_secret' => $this->githubClientSecret,
                    'code' => $code,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            $tokenData = $tokenResponse->toArray();
            if (!isset($tokenData['access_token'])) {
                throw new UnauthorizedHttpException('github', 'Не удалось получить GitHub токен');
            }

            $userResponse = $this->httpClient->request('GET', 'https://api.github.com/user', [
                'headers' => [
                    'Authorization' => "token {$tokenData['access_token']}",
                ],
            ]);

            $userData = $userResponse->toArray();
            if (!isset($userData['id'])) {
                throw new UnauthorizedHttpException('github', 'GitHub пользователь не предоставил ID');
            }

            return $this->loginWithOAuth([
                'email' => $userData['email'] ?? "{$userData['login']}@github.com",
                'fullName' => $userData['name'] ?? $userData['login'],
                'oauthId' => (string) $userData['id'],
                'provider' => 'github',
                'avatar' => $userData['avatar_url'] ?? '',
            ]);
        } catch (Exception $e) {
            throw new UnauthorizedHttpException('github', 'Ошибка обработки GitHub авторизации: ' . $e->getMessage());
        }
    }

    private function loginWithOAuth(array $oauthUser): array
    {
        $user = $this->usersService->getUserByEmail($oauthUser['email']);
        if (!$user) {
            $user = $this->usersService->createUser(
                $oauthUser['email'],
                bin2hex(random_bytes(16)),
                $oauthUser['fullName'],
                $oauthUser['avatar']
            );
        } else {
            // Проверяем, соответствует ли провайдер
            if ($user->getProvider() && $user->getProvider() !== $oauthUser['provider']) {
                throw new BadRequestHttpException(
                    "Этот email уже привязан к другому провайдеру ({$user->getProvider()})"
                );
            }
        }

        $user->setProvider($oauthUser['provider']);
        $user->setProviderId($oauthUser['oauthId']);
        $this->usersService->saveUser($user);

        return $this->generateTokens($user);
    }

    public function logout(User $user): void
    {
        $this->redisService->deleteToken("access_token:{$user->getId()}");
        $this->redisService->deleteToken("refresh_token:{$user->getId()}");
    }

    public function getUserByJwtToken(string $jwtToken): ?User
    {
        try {
            $payload = $this->jwtEncoder->decode($jwtToken);
            $email = $payload['sub'] ?? null; // Теперь sub — это email

            if (!$email) {
                throw new UnauthorizedHttpException('jwt', 'Неверный или повреждённый токен');
            }

            $user = $this->usersService->getUserByEmail($email); // Используем email вместо ID
            if (!$user || !$user->isActive()) {
                throw new UnauthorizedHttpException('jwt', 'Пользователь не найден или удалён');
            }

            return $user;
        } catch (Exception $e) {
            throw new UnauthorizedHttpException('jwt', 'Ошибка обработки токена: ' . $e->getMessage());
        }
    }

    private function generateTokens(User $user): array
    {
        $accessToken = $this->jwtManager->create($user);
        $refreshPayload = [
            'sub' => $user->getEmail(), // Используем email вместо ID
            'email' => $user->getEmail(),
            'exp' => time() + 604800,
        ];
        $refreshToken = $this->jwtEncoder->encode($refreshPayload);

        $this->redisService->setToken("access_token:{$user->getId()}", $accessToken, 3600);
        $this->redisService->setToken("refresh_token:{$user->getId()}", $refreshToken, 604800);

        return [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
                'avatar' => $user->getAvatar(),
                'createdAt' => $user->getCreatedAt()->format(\DateTime::ATOM),
                'updatedAt' => $user->getUpdatedAt()->format(\DateTime::ATOM),
            ]
        ];
    }
}