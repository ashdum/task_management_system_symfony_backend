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
    #[OA\Property(
        property: "email",
        type: "string",
        example: "user@example.com",
        description: "User's email address"
    )]
    public string $email;

    #[Assert\NotBlank(message: 'Пароль обязателен')]
    #[StrongPassword]
    #[OA\Property(
        property: "password",
        type: "string",
        example: "StrongPass123!",
        description: "User's password (must meet strength requirements)"
    )]
    public string $password;

    #[Assert\NotBlank(message: 'Имя обязательно')]
    #[Assert\Length(min: 2, max: 50, minMessage: 'Имя должно быть длиннее 2 символов', maxMessage: 'Имя должно быть короче 50 символов')]
    #[OA\Property(
        property: "fullName",
        type: "string",
        example: "John Doe",
        description: "User's full name"
    )]
    public ?string $fullName = null;

    #[OA\Property(
        property: "avatar",
        type: "string",
        example: "https://example.com/avatar.jpg",
        description: "URL to user's avatar image",
        nullable: true
    )]
    public ?string $avatar = null;
}