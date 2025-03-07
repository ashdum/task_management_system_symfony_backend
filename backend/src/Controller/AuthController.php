<?php
// backend/src/Controller/AuthController.php
namespace App\Controller;

use App\Service\AuthService;
use App\DTO\RegisterDTO;
use App\DTO\LoginDTO;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;

class AuthController
{
    private $authService;
    private $validator;
    private $serializer;

    public function __construct(
        AuthService $authService,
        ValidatorInterface $validator,
        SerializerInterface $serializer
    ) {
        $this->authService = $authService;
        $this->validator = $validator;
        $this->serializer = $serializer;
    }

    #[Route('/auth/register', name: 'auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize($request->getContent(), RegisterDTO::class, 'json');
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->authService->register($dto);
        return new JsonResponse($result, Response::HTTP_CREATED);
    }

    #[Route('/auth/login', name: 'auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize($request->getContent(), LoginDTO::class, 'json');
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->authService->login($dto);
        return new JsonResponse($result, Response::HTTP_OK);
    }

    #[Route('/auth/refresh', name: 'auth_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $refreshToken = $data['refreshToken'] ?? null;

        if (!$refreshToken) {
            return new JsonResponse(['error' => 'Refresh-токен обязателен'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->authService->refresh($refreshToken);
        return new JsonResponse($result, Response::HTTP_OK);
    }
}