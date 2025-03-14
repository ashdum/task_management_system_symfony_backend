<?php
namespace App\Domain\Auth\DTO;

use App\Shared\Validator\StrongPassword;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ChangePasswordDTO",
    title: "Change Password DTO",
    description: "Data Transfer Object for changing user password"
)]
class ChangePasswordDTO
{
    #[Assert\NotBlank(message: "Текущий пароль обязателен")]
    #[OA\Property(property: "currentPassword", type: "string", example: "StrongPass123!", description: "Current user password")]
    private string $currentPassword;

    #[Assert\NotBlank(message: "Новый пароль обязателен")]
    #[StrongPassword]
    #[OA\Property(property: "newPassword", type: "string", example: "NewPass123!", description: "New user password (must meet strength requirements)")]
    private string $newPassword;

    public function getCurrentPassword(): string
    {
        return $this->currentPassword;
    }

    public function setCurrentPassword(string $currentPassword): self
    {
        $this->currentPassword = $currentPassword;
        return $this;
    }

    public function getNewPassword(): string
    {
        return $this->newPassword;
    }

    public function setNewPassword(string $newPassword): self
    {
        $this->newPassword = $newPassword;
        return $this;
    }
}