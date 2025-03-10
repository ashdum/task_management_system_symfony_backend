<?php
namespace App\Domain\Auth\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "GoogleLoginDTO",
    title: "Google Login DTO",
    description: "Data Transfer Object for Google login"
)]
class GoogleLoginDTO
{
    #[Assert\NotBlank(message: "Google credential обязателен")]
    #[OA\Property(
        property: "credential",
        type: "string",
        example: "google-id-token",
        description: "Google OAuth credential"
    )]
    public string $credential;
}