<?php
namespace App\Domain\Auth\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "RefreshTokenDTO",
    title: "Refresh Token DTO",
    description: "Data Transfer Object for refreshing tokens"
)]
class RefreshTokenDTO
{
    #[Assert\NotBlank(message: 'Refresh-токен обязателен')]
    #[OA\Property(property: "refreshToken", type: "string", example: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...", description: "Refresh token for generating new access token")]
    private string $refreshToken;

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;
        return $this;
    }
}