<?php

namespace App\Shared\Entity;

use App\Shared\Enum\DelStatusEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Ramsey\Uuid\Uuid;
use OpenApi\Attributes as OA;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
#[OA\Schema(
    schema: "BaseEntity",
    title: "Base Entity",
    description: "Base entity with common fields for all entities"
)]
abstract class BaseEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'Ramsey\Uuid\Doctrine\UuidGenerator')]
    #[Groups(['base:read'])]
    #[OA\Property(
        property: "id",
        type: "string",
        example: "550e8400-e29b-41d4-a716-446655440000",
        description: "Unique identifier (UUID)"
    )]
    protected ?string $id = null;

    #[ORM\Column(type: 'integer', enumType: DelStatusEnum::class)]
    #[Groups(['base:read'])]
    #[OA\Property(
        property: "delStatus",
        type: "integer",
        example: 1,
        description: "Deletion status (1 = ACTIVE, 2 = DELETED)"
    )]
    protected ?DelStatusEnum $delStatus = null; // No initialization here

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['base:read'])]
    #[OA\Property(
        property: "delDate",
        type: "string",
        format: "date-time",
        example: "2025-03-09T12:00:00+00:00",
        description: "Date of deletion (if applicable)",
        nullable: true
    )]
    protected ?\DateTimeInterface $delDate = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['base:read'])]
    #[OA\Property(
        property: "createdAt",
        type: "string",
        format: "date-time",
        example: "2025-03-09T10:00:00+00:00",
        description: "Date and time of creation"
    )]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['base:read'])]
    #[OA\Property(
        property: "updatedAt",
        type: "string",
        format: "date-time",
        example: "2025-03-09T11:00:00+00:00",
        description: "Date and time of last update"
    )]
    protected ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        if ($this->id === null) {
            $this->id = Uuid::uuid4()->toString();
        }
        $this->delStatus = DelStatusEnum::ACTIVE;
	    $this->createdAt = new \DateTime();
	    $this->updatedAt = new \DateTime();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // Getters and setters
    public function getId(): ?string
    {
        return $this->id;
    }

    public function getDelStatus(): ?DelStatusEnum // Nullable return type
    {
        return $this->delStatus;
    }

    public function setDelStatus(DelStatusEnum $delStatus): self
    {
        $this->delStatus = $delStatus;
        return $this;
    }

    public function getDelDate(): ?\DateTimeInterface
    {
        return $this->delDate;
    }

    public function setDelDate(?\DateTimeInterface $delDate): self
    {
        $this->delDate = $delDate;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function softDelete(): void
    {
        $this->delStatus = DelStatusEnum::DELETED;
        $this->delDate = new \DateTime();
    }

    public function isActive(): bool
    {
        return $this->delStatus === DelStatusEnum::ACTIVE;
    }
}