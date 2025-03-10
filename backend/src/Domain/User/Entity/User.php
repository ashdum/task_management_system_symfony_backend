<?php

namespace App\Domain\User\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface; 
use App\Shared\Entity\BaseEntity;
use App\Shared\Enum\RoleEnum;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\Index(name: 'idx_user_email', columns: ['email'])]
#[OA\Schema(
    schema: "User",
    title: "User Entity",
    description: "Represents a user in the system"
)]
class User extends BaseEntity implements UserInterface, PasswordAuthenticatedUserInterface 
{
    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups(['user:read', 'user:write'])]
    #[OA\Property(
        property: "email",
        type: "string",
        example: "user@example.com",
        description: "User's email address, must be unique"
    )]
    private ?string $email = null;

    #[ORM\Column(type: 'string')]
    #[Groups(['user:write'])]
    #[OA\Property(
        property: "password",
        type: "string",
        example: "$2y$13hashedpassword...",
        description: "Hashed user password (not exposed in read operations)"
    )]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    #[OA\Property(
        property: "fullName",
        type: "string",
        example: "John Doe",
        description: "User's full name",
        nullable: true
    )]
    private ?string $fullName = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    #[OA\Property(
        property: "avatar",
        type: "string",
        example: "https://example.com/avatar.jpg",
        description: "URL to user's avatar image",
        nullable: true
    )]
    private ?string $avatar = null;

    #[ORM\Column(type: 'string', enumType: RoleEnum::class)]
    #[Groups(['user:read'])]
    #[OA\Property(
        property: "role",
        type: "string",
        example: "ROLE_USER",
        description: "User's role in the system"
    )]
    private ?RoleEnum $role = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['user:read'])]
    #[OA\Property(
        property: "provider",
        type: "string",
        example: "google",
        description: "OAuth provider (e.g., google, github)",
        nullable: true
    )]
    private ?string $provider = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['user:read'])]
    #[OA\Property(
        property: "providerId",
        type: "string",
        example: "123456789",
        description: "Unique identifier from the OAuth provider",
        nullable: true
    )]
    private ?string $providerId = null;

    public function __construct()
    {
        parent::__construct(); 
        $this->role = RoleEnum::USER;
    }

    // Getters and setters
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
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

    public function getRole(): ?RoleEnum
    {
        return $this->role;
    }

    public function setRole(RoleEnum $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    public function getProviderId(): ?string
    {
        return $this->providerId;
    }

    public function setProviderId(?string $providerId): self
    {
        $this->providerId = $providerId;
        return $this;
    }

    // UserInterface methods
    public function getRoles(): array
    {
        return [$this->role?->value ?? RoleEnum::USER->value]; // Fallback to ROLE_USER if null
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->id;
    }
}