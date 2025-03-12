<?php

namespace App\Tests\Domain\Auth\Controller;

use App\Domain\Auth\Controller\AuthController;
use App\Domain\Auth\DTO\ChangePasswordDTO;
use App\Domain\Auth\DTO\GithubLoginDTO;
use App\Domain\Auth\DTO\GoogleLoginDTO;
use App\Domain\Auth\DTO\LoginDTO;
use App\Domain\Auth\DTO\RefreshTokenDTO;
use App\Domain\Auth\DTO\RegisterDTO;
use App\Domain\Auth\Service\AuthService;
use App\Domain\User\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\SecurityBundle\Security;

class AuthControllerTest extends TestCase
{
    private AuthController $controller;
    private AuthService|MockObject $authService;
    private Security|MockObject $security;
    private SerializerInterface|MockObject $serializer;
    private ValidatorInterface|MockObject $validator;

    protected function setUp(): void
    {
        $this->authService = $this->createMock(AuthService::class);
        $this->security = $this->createMock(Security::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->controller = new AuthController(
            $this->authService,
            $this->security,
            $this->serializer,
            $this->validator
        );
    }

    private function createUser(): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn('user-id-123');
        return $user;
    }

    public function testRegisterSuccess(): void
    {
        $dto = new RegisterDTO();
        $dto->setEmail('test@example.com');
        $dto->setPassword('StrongPass123!');
        $request = new Request([], [], [], [], [], [], json_encode(['email' => 'test@example.com', 'password' => 'StrongPass123!']));
        $result = ['accessToken' => 'token', 'refreshToken' => 'refresh', 'user' => ['id' => 'user-id-123']];

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with($request->getContent(), RegisterDTO::class, 'json')
            ->willReturn($dto);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($dto)
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->authService->expects($this->once())
            ->method('register')
            ->with($dto)
            ->willReturn($result);

        $response = $this->controller->register($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($result), $response->getContent());
    }

    public function testLoginSuccess(): void
    {
        $dto = new LoginDTO();
        $dto->setEmail('test@example.com');
        $dto->setPassword('StrongPass123!');
        $request = new Request([], [], [], [], [], [], json_encode(['email' => 'test@example.com', 'password' => 'StrongPass123!']));
        $result = ['accessToken' => 'token', 'refreshToken' => 'refresh', 'user' => ['id' => 'user-id-123']];

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with($request->getContent(), LoginDTO::class, 'json')
            ->willReturn($dto);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($dto)
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->authService->expects($this->once())
            ->method('login')
            ->with($dto)
            ->willReturn($result);

        $response = $this->controller->login($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($result), $response->getContent());
    }

    public function testRefreshSuccess(): void
    {
        $dto = new RefreshTokenDTO();
        $dto->setRefreshToken('refresh-token');
        $request = new Request([], [], [], [], [], [], json_encode(['refreshToken' => 'refresh-token']));
        $result = ['accessToken' => 'new-token', 'refreshToken' => 'new-refresh', 'user' => ['id' => 'user-id-123']];

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with($request->getContent(), RefreshTokenDTO::class, 'json')
            ->willReturn($dto);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($dto)
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->authService->expects($this->once())
            ->method('refresh')
            ->with('refresh-token')
            ->willReturn($result);

        $response = $this->controller->refresh($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($result), $response->getContent());
    }

    public function testChangePasswordSuccess(): void
    {
        $user = $this->createUser();
        $dto = new ChangePasswordDTO();
        $dto->setCurrentPassword('OldPass123!');
        $dto->setNewPassword('NewPass123!');
        $request = new Request([], [], [], [], [], [], json_encode(['currentPassword' => 'OldPass123!', 'newPassword' => 'NewPass123!']));

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with($request->getContent(), ChangePasswordDTO::class, 'json')
            ->willReturn($dto);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($dto)
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->authService->expects($this->once())
            ->method('changePassword')
            ->with($user, 'OldPass123!', 'NewPass123!');

        $response = $this->controller->changePassword($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"message": "Пароль успешно изменен"}', $response->getContent());
    }

    public function testChangePasswordUnauthorized(): void
    {
        $request = new Request([], [], [], [], [], [], '{}');

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage('Пользователь не авторизован');

        $this->controller->changePassword($request);
    }

    public function testGoogleLoginSuccess(): void
    {
        $dto = new GoogleLoginDTO();
        $dto->setCredential('google-credential');
        $request = new Request([], [], [], [], [], [], json_encode(['credential' => 'google-credential']));
        $result = ['accessToken' => 'token', 'refreshToken' => 'refresh', 'user' => ['id' => 'user-id-123']];

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with($request->getContent(), GoogleLoginDTO::class, 'json')
            ->willReturn($dto);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($dto)
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->authService->expects($this->once())
            ->method('googleLogin')
            ->with('google-credential')
            ->willReturn($result);

        $response = $this->controller->googleLogin($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($result), $response->getContent());
    }

    public function testGithubLoginSuccess(): void
    {
        $dto = new GithubLoginDTO();
        $dto->setCode('github-code');
        $request = new Request([], [], [], [], [], [], json_encode(['code' => 'github-code']));
        $result = ['accessToken' => 'token', 'refreshToken' => 'refresh', 'user' => ['id' => 'user-id-123']];

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with($request->getContent(), GithubLoginDTO::class, 'json')
            ->willReturn($dto);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($dto)
            ->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->authService->expects($this->once())
            ->method('githubLogin')
            ->with('github-code')
            ->willReturn($result);

        $response = $this->controller->githubLogin($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($result), $response->getContent());
    }

    public function testLogoutSuccess(): void
    {
        $user = $this->createUser();

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->authService->expects($this->once())
            ->method('logout')
            ->with($user);

        $response = $this->controller->logout();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"message": "Успешно вышли из системы"}', $response->getContent());
    }

    public function testLogoutUnauthorized(): void
    {
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage('Пользователь не авторизован');

        $this->controller->logout();
    }
}