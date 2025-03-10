<?php

namespace App\Shared\Security;

use App\Domain\Auth\Service\AuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(private AuthService $authService) {}

    public function supports(Request $request): ?bool
    {
        // Пропускаем публичные эндпоинты
        $path = $request->getPathInfo();
        if (preg_match('#^/auth/(login|register|refresh|google|github)$#', $path)) {
            return false; // Не применяем аутентификацию
        }

        // Применяем аутентификацию только для запросов с заголовком Authorization
        return $request->headers->has('Authorization') &&
               str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');
        $jwtToken = substr($authHeader, 7);

        $user = $this->authService->getUserByJwtToken($jwtToken);
        if (!$user) {
            throw new AuthenticationException('Пользователь не авторизован или токен недействителен');
        }

        return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier()));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?JsonResponse
    {
        return null; // Продолжаем выполнение запроса
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?JsonResponse
    {
        return new JsonResponse(['error' => $exception->getMessage()], 401);
    }
}