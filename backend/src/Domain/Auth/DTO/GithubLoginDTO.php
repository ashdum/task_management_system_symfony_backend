<?php
namespace App\Domain\Auth\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "GithubLoginDTO",
    title: "GitHub Login DTO",
    description: "Data Transfer Object for GitHub login"
)]
class GithubLoginDTO
{
    #[Assert\NotBlank(message: "GitHub code обязателен")]
    #[OA\Property(property: "code", type: "string", example: "github-auth-code", description: "GitHub authorization code")]
    private string $code;

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }
}