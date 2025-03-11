<?php
namespace App\Domain\Auth\Service;

use App\Domain\User\Entity\User;
use App\Domain\User\Service\UsersService;
use Google\Client as GoogleClient;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OAuthService
{
    private UsersService $usersService;
    private GoogleClient $googleClient;
    private HttpClientInterface $httpClient;
    private string $googleClientId;
    private string $githubClientId;
    private string $githubClientSecret;

    public function __construct(
        UsersService $usersService,
        GoogleClient $googleClient,
        HttpClientInterface $httpClient,
        string $googleClientId,
        string $githubClientId,
        string $githubClientSecret
    ) {
        $this->usersService = $usersService;
        $this->googleClient = $googleClient;
        $this->httpClient = $httpClient;
        $this->googleClientId = $googleClientId;
        $this->githubClientId = $githubClientId;
        $this->githubClientSecret = $githubClientSecret;
    }

    public function googleLogin(string $credential): User
    {
        $payload = $this->googleClient->verifyIdToken($credential, $this->googleClientId);
        if ($payload === false || !isset($payload['email'])) {
            throw new UnauthorizedHttpException('google', 'Неверный Google токен');
        }

        return $this->loginWithOAuth([
            'email' => $payload['email'],
            'fullName' => $payload['name'] ?? explode('@', $payload['email'])[0],
            'oauthId' => $payload['sub'],
            'provider' => 'google',
            'avatar' => $payload['picture'] ?? '',
        ]);
    }

    public function githubLogin(string $code): User
    {
        $tokenResponse = $this->httpClient->request('POST', 'https://github.com/login/oauth/access_token', [
            'json' => [
                'client_id' => $this->githubClientId,
                'client_secret' => $this->githubClientSecret,
                'code' => $code,
            ],
            'headers' => ['Accept' => 'application/json'],
        ]);

        $tokenData = $tokenResponse->toArray();
        if (!isset($tokenData['access_token'])) {
            throw new UnauthorizedHttpException('github', 'Не удалось получить GitHub токен');
        }

        $userResponse = $this->httpClient->request('GET', 'https://api.github.com/user', [
            'headers' => ['Authorization' => "token {$tokenData['access_token']}"],
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
    }

    private function loginWithOAuth(array $oauthUser): User
    {
        $user = $this->usersService->getUserByEmail($oauthUser['email']);
        if (!$user) {
            $user = $this->usersService->createUser(
                $oauthUser['email'],
                bin2hex(random_bytes(16)),
                $oauthUser['fullName'],
                $oauthUser['avatar']
            );
        } elseif ($user->getProvider() && $user->getProvider() !== $oauthUser['provider']) {
            throw new BadRequestHttpException("Этот email уже привязан к другому провайдеру ({$user->getProvider()})");
        }

        $user->setProvider($oauthUser['provider']);
        $user->setProviderId($oauthUser['oauthId']);
        $this->usersService->saveUser($user);

        return $user;
    }
}