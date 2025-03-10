<?php

namespace App\Domain\Auth\Controller;

use App\Domain\Auth\DTO\LoginDTO;
use App\Domain\Auth\DTO\RegisterDTO;
use App\Domain\Auth\DTO\RefreshTokenDTO;
use App\Domain\Auth\DTO\ChangePasswordDTO;
use App\Domain\Auth\DTO\GoogleLoginDTO;
use App\Domain\Auth\DTO\GithubLoginDTO;
use App\Domain\Auth\Service\AuthService;
use App\Domain\User\Entity\User;
use Exception;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use OpenApi\Attributes as OA;

class AuthController
{
    private AuthService $authService;
    private ValidatorInterface $validator;
    private SerializerInterface $serializer;
    private Security $security;

    public function __construct(
        AuthService $authService,
        ValidatorInterface $validator,
        SerializerInterface $serializer,
        Security $security
    ) {
        $this->authService = $authService;
        $this->validator = $validator;
        $this->serializer = $serializer;
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
            new OA\Response(
                response: 400,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "errors", type: "string", example: "The email field is required.")
                    ],
                    type: "object"
                )
            ),
            new OA\Response(
                response: 409,
                description: "User with this email already exists",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Пользователь с таким email уже существует")
                    ],
                    type: "object"
                )
            )
        ]
    )]
    public function register(Request $request): JsonResponse
    {
        try {
            $dto = $this->serializer->deserialize($request->getContent(), RegisterDTO::class, 'json');
            $errors = $this->validator->validate($dto);

            if (count($errors) > 0) {
                return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
            }

            $result = $this->authService->register($dto);
            return new JsonResponse($result, Response::HTTP_CREATED);
        } catch (HttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Внутренняя ошибка сервера'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
            new OA\Response(
                response: 400,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "errors", type: "string", example: "The password field is required.")
                    ],
                    type: "object"
                )
            ),
            new OA\Response(
                response: 401,
                description: "Invalid credentials",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Неверные учетные данные")
                    ],
                    type: "object"
                )
            )
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        try {
            $dto = $this->serializer->deserialize($request->getContent(), LoginDTO::class, 'json');
            $errors = $this->validator->validate($dto);

            if (count($errors) > 0) {
                return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
            }

            $result = $this->authService->login($dto);
            return new JsonResponse($result, Response::HTTP_OK);
        } catch (HttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Внутренняя ошибка сервера'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
            new OA\Response(
                response: 400,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "errors", type: "string", example: "The refreshToken field is required.")
                    ],
                    type: "object"
                )
            ),
            new OA\Response(
                response: 401,
                description: "Invalid or expired refresh token",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Ошибка валидации refresh-токена")
                    ],
                    type: "object"
                )
            )
        ]
    )]
    public function refresh(Request $request): JsonResponse
    {
        try {
            $dto = $this->serializer->deserialize($request->getContent(), RefreshTokenDTO::class, 'json');
            $errors = $this->validator->validate($dto);

            if (count($errors) > 0) {
                return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
            }

            $result = $this->authService->refresh($dto->refreshToken);
            return new JsonResponse($result, Response::HTTP_OK);
        } catch (HttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Внутренняя ошибка сервера'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
            new OA\Response(
                response: 200,
                description: "Password changed successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Пароль успешно изменен")
                    ],
                    type: "object"
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid current password or validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Текущий пароль неверный")
                    ],
                    type: "object"
                )
            ),
            new OA\Response(
                response: 401,
                description: "User not authenticated",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Пользователь не авторизован")
                    ],
                    type: "object"
                )
            )
        ]
    )]
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $user = $this->security->getUser();
            if (!$user) {
                throw new HttpException(401, 'Пользователь не авторизован');
            }
            $dto = $this->serializer->deserialize($request->getContent(), ChangePasswordDTO::class, 'json');
            $errors = $this->validator->validate($dto);
            if (count($errors) > 0) {
                return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
            }

            $this->authService->changePassword($user, $dto->currentPassword, $dto->newPassword);
            return new JsonResponse(['message' => 'Пароль успешно изменен'], Response::HTTP_OK);
        } catch (HttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Внутренняя ошибка сервера'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
            new OA\Response(
                response: 400,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "errors", type: "string", example: "The credential field is required.")
                    ],
                    type: "object"
                )
            ),
            new OA\Response(
                response: 401,
                description: "Invalid Google token",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Неверный Google токен")
                    ],
                    type: "object"
                )
            )
        ]
    )]
    public function googleLogin(Request $request): JsonResponse
    {
        try {
            $dto = $this->serializer->deserialize($request->getContent(), GoogleLoginDTO::class, 'json');
            $errors = $this->validator->validate($dto);

            if (count($errors) > 0) {
                return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
            }

            $result = $this->authService->googleLogin($dto->credential);
            return new JsonResponse($result, Response::HTTP_OK);
        } catch (HttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Внутренняя ошибка сервера'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
            new OA\Response(
                response: 400,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "errors", type: "string", example: "The code field is required.")
                    ],
                    type: "object"
                )
            ),
            new OA\Response(
                response: 401,
                description: "Invalid GitHub code",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Ошибка обработки GitHub токена")
                    ],
                    type: "object"
                )
            )
        ]
    )]
    public function githubLogin(Request $request): JsonResponse
    {
        try {
            $dto = $this->serializer->deserialize($request->getContent(), GithubLoginDTO::class, 'json');
            $errors = $this->validator->validate($dto);

            if (count($errors) > 0) {
                return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
            }

            $result = $this->authService->githubLogin($dto->code);
            return new JsonResponse($result, Response::HTTP_OK);
        } catch (HttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Внутренняя ошибка сервера'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/auth/logout', name: 'auth_logout', methods: ['POST'])]
    #[OA\Post(
        path: "/auth/logout",
        summary: "Logout a user",
        tags: ["Authentication"],
        security: [["JWT-auth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "User logged out successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Успешно вышли из системы")
                    ],
                    type: "object"
                )
            ),
            new OA\Response(
                response: 401,
                description: "User not authenticated",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Пользователь не авторизован")
                    ],
                    type: "object"
                )
            )
        ]
    )]
    public function logout(): JsonResponse
    {
        try {
            $user = $this->security->getUser();
            if (!$user) {
                throw new HttpException(401, 'Пользователь не авторизован');
            }

            $this->authService->logout($user);
            return new JsonResponse(['message' => 'Успешно вышли из системы'], Response::HTTP_OK);
        } catch (HttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Внутренняя ошибка сервера'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}