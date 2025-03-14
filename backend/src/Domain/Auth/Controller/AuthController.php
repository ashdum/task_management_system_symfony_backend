<?php
namespace App\Domain\Auth\Controller;

use App\Domain\Auth\DTO\ChangePasswordDTO;
use App\Domain\Auth\DTO\GithubLoginDTO;
use App\Domain\Auth\DTO\GoogleLoginDTO;
use App\Domain\Auth\DTO\LoginDTO;
use App\Domain\Auth\DTO\RefreshTokenDTO;
use App\Domain\Auth\DTO\RegisterDTO;
use App\Domain\Auth\Service\AuthService;
use App\Domain\User\Entity\User;
use App\Shared\Controller\BaseController;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

class AuthController extends BaseController
{
    private AuthService $authService;
    private Security $security;

    public function __construct(
        AuthService $authService,
        Security $security,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        parent::__construct($serializer, $validator);
        $this->authService = $authService;
        $this->security = $security;
    }

    #[Route('/auth/register', name: 'auth_register', methods: ['POST'])]
    #[OA\Post(
        path: "/auth/register",
        summary: "Register a new user",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: RegisterDTO::class))
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "User registered successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "accessToken", type: "string", example: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
                        new OA\Property(property: "refreshToken", type: "string", example: "refresh_token_string"),
                        new OA\Property(property: "user", ref: new Model(type: User::class, groups: ["user:read"]))
                    ],
                    type: "object"
                )
            ),
            new OA\Response(response: 400, description: "Validation error"),
            new OA\Response(response: 409, description: "User with this email already exists")
        ]
    )]
    public function register(Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, RegisterDTO::class);
        $result = $this->authService->register($dto);
        return $this->jsonResponse($result, Response::HTTP_CREATED);
    }

    #[Route('/auth/login', name: 'auth_login', methods: ['POST'])]
    #[OA\Post(
        path: "/auth/login",
        summary: "Login a user",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: LoginDTO::class))
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "User logged in successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "accessToken", type: "string", example: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
                        new OA\Property(property: "refreshToken", type: "string", example: "refresh_token_string"),
                        new OA\Property(property: "user", ref: new Model(type: User::class, groups: ["user:read"]))
                    ],
                    type: "object"
                )
            ),
            new OA\Response(response: 400, description: "Validation error"),
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, LoginDTO::class);
        $result = $this->authService->login($dto);
        return $this->jsonResponse($result);
    }

    #[Route('/auth/refresh', name: 'auth_refresh', methods: ['POST'])]
    #[OA\Post(
        path: "/auth/refresh",
        summary: "Refresh access token",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: RefreshTokenDTO::class))
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Token refreshed successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "accessToken", type: "string", example: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
                        new OA\Property(property: "refreshToken", type: "string", example: "new_refresh_token_string"),
                        new OA\Property(property: "user", ref: new Model(type: User::class, groups: ["user:read"]))
                    ],
                    type: "object"
                )
            ),
            new OA\Response(response: 400, description: "Validation error"),
            new OA\Response(response: 401, description: "Invalid or expired refresh token")
        ]
    )]
    public function refresh(Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, RefreshTokenDTO::class);
        $result = $this->authService->refresh($dto->getRefreshToken());
        return $this->jsonResponse($result);
    }

    #[Route('/auth/change-password', name: 'auth_change_password', methods: ['POST'])]
    #[OA\Post(
        path: "/auth/change-password",
        summary: "Change user password",
        tags: ["Authentication"],
        security: [["JWT-auth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: ChangePasswordDTO::class))
        ),
        responses: [
            new OA\Response(response: 200, description: "Password changed successfully"),
            new OA\Response(response: 400, description: "Invalid current password or validation error"),
            new OA\Response(response: 401, description: "User not authenticated")
        ]
    )]
    public function changePassword(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new HttpException(401, 'Пользователь не авторизован');
        }

        $dto = $this->deserializeAndValidate($request, ChangePasswordDTO::class);
        $this->authService->changePassword($user, $dto->getCurrentPassword(), $dto->getNewPassword());
        return $this->jsonResponse(['message' => 'Пароль успешно изменен']);
    }

    #[Route('/auth/google', name: 'auth_google', methods: ['POST'])]
    #[OA\Post(
        path: "/auth/google",
        summary: "Login with Google OAuth",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: GoogleLoginDTO::class))
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "User logged in successfully via Google",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "accessToken", type: "string", example: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
                        new OA\Property(property: "refreshToken", type: "string", example: "refresh_token_string"),
                        new OA\Property(property: "user", ref: new Model(type: User::class, groups: ["user:read"]))
                    ],
                    type: "object"
                )
            ),
            new OA\Response(response: 400, description: "Validation error"),
            new OA\Response(response: 401, description: "Invalid Google token")
        ]
    )]
    public function googleLogin(Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, GoogleLoginDTO::class);
        $result = $this->authService->googleLogin($dto->getCredential());
        return $this->jsonResponse($result);
    }

    #[Route('/auth/github', name: 'auth_github', methods: ['POST'])]
    #[OA\Post(
        path: "/auth/github",
        summary: "Login with GitHub OAuth",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: GithubLoginDTO::class))
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "User logged in successfully via GitHub",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "accessToken", type: "string", example: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
                        new OA\Property(property: "refreshToken", type: "string", example: "refresh_token_string"),
                        new OA\Property(property: "user", ref: new Model(type: User::class, groups: ["user:read"]))
                    ],
                    type: "object"
                )
            ),
            new OA\Response(response: 400, description: "Validation error"),
            new OA\Response(response: 401, description: "Invalid GitHub code")
        ]
    )]
    public function githubLogin(Request $request): JsonResponse
    {
        $dto = $this->deserializeAndValidate($request, GithubLoginDTO::class);
        $result = $this->authService->githubLogin($dto->getCode());
        return $this->jsonResponse($result);
    }

    #[Route('/auth/logout', name: 'auth_logout', methods: ['POST'])]
    #[OA\Post(
        path: "/auth/logout",
        summary: "Logout a user",
        tags: ["Authentication"],
        security: [["JWT-auth" => []]],
        responses: [
            new OA\Response(response: 200, description: "User logged out successfully"),
            new OA\Response(response: 401, description: "User not authenticated")
        ]
    )]
    public function logout(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new HttpException(401, 'Пользователь не авторизован');
        }

        $this->authService->logout($user);
        return $this->jsonResponse(['message' => 'Успешно вышли из системы']);
    }
}