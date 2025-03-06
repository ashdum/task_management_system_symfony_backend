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

class AuthService
{
	private $em;
	private $jwtManager;
	private $passwordHasher;
	private $redis;

	public function __construct(
		EntityManagerInterface $em,
		JWTTokenManagerInterface $jwtManager,
		UserPasswordHasherInterface $passwordHasher,
		Client $redis
	)
	{
		$this->em = $em;
		$this->jwtManager = $jwtManager;
		$this->passwordHasher = $passwordHasher;
		$this->redis = $redis;
	}

	/**
	 * @throws \Exception
	 */
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

	private function generateTokens(User $user): array
	{
		$accessToken = $this->jwtManager->create($user);
		$refreshToken = $this->jwtManager->createFromPayload($user, [
			'sub' => $user->getId(),
			'email' => $user->getEmail()
		], ['ttl' => 604800]); // 7 дней

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
				'createdAt' => $user->getCreatedAt()->format(\DateTime::ISO8601),
				'updatedAt' => $user->getUpdatedAt()->format(\DateTime::ISO8601),
			]
		];
	}
}