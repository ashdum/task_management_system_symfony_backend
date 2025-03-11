<?php
namespace App\Domain\Auth\Service;

use App\Domain\Auth\DTO\LoginDTO;
use App\Domain\Auth\DTO\RegisterDTO;
use App\Domain\User\Entity\User;
use App\Domain\User\Service\UsersService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    private UsersService $usersService;
    private UserPasswordHasherInterface $passwordHasher;
    private TokenService $tokenService;
    private OAuthService $oauthService;

    public function __construct(
        UsersService $usersService,
        UserPasswordHasherInterface $passwordHasher,
        TokenService $tokenService,
        OAuthService $oauthService
    ) {
        $this->usersService = $usersService;
        $this->passwordHasher = $passwordHasher;
        $this->tokenService = $tokenService;
        $this->oauthService = $oauthService;
    }

    public function register(RegisterDTO $dto): array
    {
        if ($this->usersService->getUserByEmail($dto->getEmail())) {
            throw new BadRequestHttpException('Пользователь с таким email уже существует', null, 409);
        }

        $user = $this->usersService->createUser(
            $dto->getEmail(),
            $dto->getPassword(),
            $dto->getFullName(),
            $dto->getAvatar()
        );
        return $this->tokenService->generateTokens($user);
    }

    public function login(LoginDTO $dto): array
    {
        $user = $this->usersService->getUserByEmail($dto->getEmail());
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $dto->getPassword())) {
            throw new UnauthorizedHttpException('jwt', 'Неверные учетные данные');
        }

        return $this->tokenService->generateTokens($user);
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            throw new BadRequestHttpException('Текущий пароль неверный');
        }

        $this->usersService->changePassword($user, $newPassword);
        $this->tokenService->logout($user);
    }

    public function googleLogin(string $credential): array
    {
        $user = $this->oauthService->googleLogin($credential);
        return $this->tokenService->generateTokens($user);
    }

    public function githubLogin(string $code): array
    {
        $user = $this->oauthService->githubLogin($code);
        return $this->tokenService->generateTokens($user);
    }

    public function logout(User $user): void
    {
        $this->tokenService->logout($user);
    }

    public function refresh(string $refreshToken): array
    {
        return $this->tokenService->refresh($refreshToken);
    }

    public function getUserByJwtToken(string $jwtToken): ?User
    {
        return $this->tokenService->getUserByJwtToken($jwtToken);
    }
}