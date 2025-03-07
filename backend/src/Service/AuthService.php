<?php
// backend/src/Service/AuthService.php
namespace App\Service;

use App\Entity\User;
use App\DTO\RegisterDTO;
use App\DTO\LoginDTO;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Predis\Client;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

class AuthService
{
    private $em;
    private $jwtManager;
    private $passwordHasher;
    private $redis;
    private $jwtEncoder;

    public function __construct(
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $passwordHasher,
        Client $redis,
        JWTEncoderInterface $jwtEncoder
    ) {
        $this->em = $em;
        $this->jwtManager = $jwtManager;
        $this->passwordHasher = $passwordHasher;
        $this->redis = $redis;
        $this->jwtEncoder = $jwtEncoder;
    }

    public function register(RegisterDTO $dto): array
    {
        $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $dto->email]);
        if ($existingUser) {
            throw new \Exception('Пользователь с таким email уже существует');
        }

        $user = new User();
        $user->setEmail($dto->email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
        $user->setFullName($dto->fullName);
        $user->setAvatar($dto->avatar);

        $this->em->persist($user);
        $this->em->flush();

        return $this->generateTokens($user);
    }

    public function login(LoginDTO $dto): array
    {
        $user = $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('u')
            ->where('u.email = :email')
            ->setParameter('email', $dto->email)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $dto->password)) {
            throw new UnauthorizedHttpException('jwt', 'Неверные учетные данные');
        }

        return $this->generateTokens($user);
    }

    public function refresh(string $refreshToken): array
    {
        try {
            // Декодируем refresh-токен
            $payload = $this->jwtEncoder->decode($refreshToken);
            $userId = $payload['sub'] ?? null;

            if (!$userId) {
                throw new UnauthorizedHttpException('jwt', 'Неверный refresh-токен');
            }

            // Проверяем, существует ли токен в Redis
            $storedRefreshToken = $this->redis->get("refresh_token:{$userId}");
            if (!$storedRefreshToken || $storedRefreshToken !== $refreshToken) {
                throw new UnauthorizedHttpException('jwt', 'Refresh-токен недействителен или истёк');
            }

            // Находим пользователя
            $user = $this->em->getRepository(User::class)->find($userId);
            if (!$user || !$user->isActive()) {
                throw new UnauthorizedHttpException('jwt', 'Пользователь не найден или удалён');
            }

            // Генерируем новые токены
            return $this->generateTokens($user);
        } catch (JWTDecodeFailureException $e) {
            throw new UnauthorizedHttpException('jwt', 'Ошибка валидации refresh-токена: ' . $e->getMessage());
        }
    }

    private function generateTokens(User $user): array
    {
        // Access-токен с TTL из конфигурации (по умолчанию 3600 секунд)
        $accessToken = $this->jwtManager->create($user);

        // Refresh-токен с кастомным TTL (7 дней)
        $refreshPayload = [
            'sub' => $user->getId(),
            'email' => $user->getEmail(),
            'exp' => time() + 604800, // Устанавливаем время истечения вручную (7 дней)
        ];
        $refreshToken = $this->jwtEncoder->encode($refreshPayload);

        $this->redis->setex("access_token:{$user->getId()}", 3600, $accessToken);
        $this->redis->setex("refresh_token:{$user->getId()}", 604800, $refreshToken);

        return [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
                'avatar' => $user->getAvatar(),
                'createdAt' => $user->getCreatedAt()->format(\DateTime::ATOM), // Замена ISO8601 на ATOM
                'updatedAt' => $user->getUpdatedAt()->format(\DateTime::ATOM), // Замена ISO8601 на ATOM
            ]
        ];
    }
}