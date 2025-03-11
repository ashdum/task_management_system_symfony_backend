<?php
namespace App\Domain\Auth\Service;

use App\Domain\User\Entity\User;
use App\Domain\User\Service\UsersService;
use App\Shared\Service\RedisService;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class TokenService
{
    private JWTTokenManagerInterface $jwtManager;
    private JWTEncoderInterface $jwtEncoder;
    private RedisService $redisService;
    private UsersService $usersService;
    private int $refreshTtl;

    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        JWTEncoderInterface $jwtEncoder,
        RedisService $redisService,
        UsersService $usersService,
        int $refreshTtl
    ) {
        $this->jwtManager = $jwtManager;
        $this->jwtEncoder = $jwtEncoder;
        $this->redisService = $redisService;
        $this->usersService = $usersService;
        $this->refreshTtl = $refreshTtl;
    }

    public function generateTokens(User $user): array
    {
        $accessToken = $this->jwtManager->create($user);
        $refreshPayload = [
            'sub' => $user->getId(),
            'email' => $user->getEmail(),
            'exp' => time() + $this->refreshTtl,
        ];
        $refreshToken = $this->jwtEncoder->encode($refreshPayload);

        $this->redisService->setToken("access_token:{$user->getId()}", $accessToken, 3600);
        $this->redisService->setToken("refresh_token:{$user->getId()}", $refreshToken, $this->refreshTtl);

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

    public function refresh(string $refreshToken): array
    {
        $payload = $this->jwtEncoder->decode($refreshToken);
        $userId = $payload['sub'] ?? null;

        if (!$userId || $this->redisService->getToken("refresh_token:{$userId}") !== $refreshToken) {
            throw new UnauthorizedHttpException('jwt', 'Refresh-токен недействителен или истёк');
        }

        $user = $this->usersService->getUser($userId);
        if (!$user || !$user->isActive()) {
            throw new UnauthorizedHttpException('jwt', 'Пользователь не найден или удалён');
        }

        return $this->generateTokens($user);
    }

    public function logout(User $user): void
    {
        $this->redisService->deleteToken("access_token:{$user->getId()}");
        $this->redisService->deleteToken("refresh_token:{$user->getId()}");
    }

    public function getUserByJwtToken(string $jwtToken): ?User
    {
        $payload = $this->jwtEncoder->decode($jwtToken);
        $userId = $payload['sub'] ?? null;

        if (!$userId || $this->redisService->getToken("access_token:{$userId}") !== $jwtToken) {
            throw new UnauthorizedHttpException('jwt', 'Токен недействителен или был отозван');
        }

        $user = $this->usersService->getUser($userId);
        if (!$user || !$user->isActive()) {
            throw new UnauthorizedHttpException('jwt', 'Пользователь не найден или удалён');
        }

        return $user;
    }
}