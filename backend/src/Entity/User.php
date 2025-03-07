<?php
// backend/src/Entity/User.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface; 
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Enum\RoleEnum;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\Index(name: 'idx_user_email', columns: ['email'])]
#[ApiResource(
    normalizationContext: ['groups' => ['user:read', 'base:read']],
    denormalizationContext: ['groups' => ['user:write']],
)]
class User extends BaseEntity implements UserInterface, PasswordAuthenticatedUserInterface 
{
    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $email = null;

    #[ORM\Column(type: 'string')]
    #[Groups(['user:write'])]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $fullName = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $avatar = null;

    #[ORM\Column(type: 'string', enumType: RoleEnum::class)]
    #[Groups(['user:read'])]
    private RoleEnum $role = RoleEnum::USER;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['user:read'])]
    private ?string $provider = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['user:read'])]
    private ?string $providerId = null;

    public function __construct()
    {
        parent::__construct(); 
    }

    // Getters and setters
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function getPassword(): ?string { return $this->password; } 
    public function setPassword(string $password): self { $this->password = $password; return $this; }
    public function getFullName(): ?string { return $this->fullName; }
    public function setFullName(?string $fullName): self { $this->fullName = $fullName; return $this; }
    public function getAvatar(): ?string { return $this->avatar; }
    public function setAvatar(?string $avatar): self { $this->avatar = $avatar; return $this; }
    public function getRole(): RoleEnum { return $this->role; }
    public function setRole(RoleEnum $role): self { $this->role = $role; return $this; }
    public function getProvider(): ?string { return $this->provider; }
    public function setProvider(?string $provider): self { $this->provider = $provider; return $this; }
    public function getProviderId(): ?string { return $this->providerId; }
    public function setProviderId(?string $providerId): self { $this->providerId = $providerId; return $this; }

    // UserInterface methods
    public function getRoles(): array 
    { 
        return [$this->role->value];
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
        return (string) $this->email;
    }
}