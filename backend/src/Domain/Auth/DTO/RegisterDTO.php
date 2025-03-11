<?php
namespace App\Domain\Auth\DTO;

use App\Shared\Validator\StrongPassword;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "RegisterDTO",
    title: "Register DTO",
    description: "Data Transfer Object for user registration"
)]
class RegisterDTO
{
    #[Assert\NotBlank(message: 'Email обязателен')]
    #[Assert\Email(message: 'Неверный формат email')]
    #[OA\Property(property: "email", type: "string", example: "user@example.com")]
    private string $email;

    #[Assert\NotBlank(message: 'Пароль обязателен')]
    #[StrongPassword]
    #[OA\Property(property: "password", type: "string", example: "StrongPass123!")]
    private string $password;

    #[Assert\NotBlank(message: 'Имя обязательно')]
    #[Assert\Length(min: 2, max: 50, minMessage: 'Имя должно быть длиннее 2 символов', maxMessage: 'Имя должно быть короче 50 символов')]
    #[OA\Property(property: "fullName", type: "string", example: "John Doe")]
    private ?string $fullName;

    #[OA\Property(property: "avatar", type: "string", example: "https://example.com/avatar.jpg", nullable: true)]
    private ?string $avatar = null;

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

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): self
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;
        return $this;
    }
}