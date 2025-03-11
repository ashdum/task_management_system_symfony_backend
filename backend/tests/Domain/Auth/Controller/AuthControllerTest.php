<?php
namespace App\Tests\Domain\Auth\Controller;

use App\Domain\Auth\Controller\AuthController;
use App\Domain\Auth\DTO\LoginDTO;
use App\Domain\Auth\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthControllerTest extends WebTestCase
{
    private $authService;
    private $serializer;
    private $validator;

    protected function setUp(): void
    {
        $this->authService = $this->createMock(AuthService::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
    }

    public function testLoginSuccess(): void
    {
        $dto = new LoginDTO();
        $dto->setEmail('test@example.com');
        $dto->setPassword('StrongPass123!');

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->willReturn($dto);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn([]);

        $this->authService->expects($this->once())
            ->method('login')
            ->with($dto)
            ->willReturn(['accessToken' => 'token']);

        $controller = new AuthController($this->authService, $this->createMock(\Symfony\Bundle\SecurityBundle\Security::class), $this->serializer, $this->validator);
        $request = new Request([], [], [], [], [], [], json_encode(['email' => 'test@example.com', 'password' => 'StrongPass123!']));

        $response = $controller->login($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"accessToken": "token"}', $response->getContent());
    }
}