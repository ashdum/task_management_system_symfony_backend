<?php
// backend/src/Controller/AuthController.php
namespace App\Controller;

use App\Service\AuthService;
use App\DTO\RegisterDTO;
use App\DTO\LoginDTO;
use App\DTO\RefreshTokenDTO;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

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
		$dto = $this->serializer->deserialize($request->getContent(), RefreshTokenDTO::class, 'json');
		$errors = $this->validator->validate($dto);

		if (count($errors) > 0) {
			return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
		}

		$result = $this->authService->refresh($dto->refreshToken);
		return new JsonResponse($result, Response::HTTP_OK);
	}

	#[Route('/auth/logout', name: 'auth_logout', methods: ['POST'])]
	public function logout(): JsonResponse
	{
		$user = $this->security->getUser();
		if (!$user) {
			throw new UnauthorizedHttpException('jwt', 'Пользователь не авторизован');
		}

		$this->authService->logout($user);
		return new JsonResponse(['message' => 'Успешно вышли из системы'], Response::HTTP_OK);
	}
}