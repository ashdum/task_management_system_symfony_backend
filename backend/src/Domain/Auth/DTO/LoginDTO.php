<?php
namespace App\Domain\Auth\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "LoginDTO",
    title: "Login DTO",
    description: "Data Transfer Object for user login"
)]
class LoginDTO
{
    #[Assert\NotBlank(message: 'Email обязателен')]
    #[Assert\Email(message: 'Неверный формат email')]
    #[OA\Property(property: "email", type: "string", example: "user@example.com", description: "User's email address")]
    private string $email;

    #[Assert\NotBlank(message: 'Пароль обязателен')]
    #[Assert\Length(min: 6, minMessage: 'Пароль должен быть длиннее 6 символов')]
    #[OA\Property(property: "password", type: "string", example: "StrongPass123!", description: "User's password")]
    private string $password;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }
}