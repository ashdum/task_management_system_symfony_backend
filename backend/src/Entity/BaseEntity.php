<?php
// backend/src/Entity/BaseEntity.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Enum\DelStatusEnum;
use Ramsey\Uuid\Uuid;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks] // Включаем поддержку lifecycle callbacks
abstract class BaseEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'Ramsey\Uuid\Doctrine\UuidGenerator')]
    #[Groups(['base:read'])]
    protected ?string $id = null;

    #[ORM\Column(type: 'integer', enumType: DelStatusEnum::class)]
    #[Groups(['base:read'])]
    protected DelStatusEnum $delStatus = DelStatusEnum::ACTIVE;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['base:read'])]
    protected ?\DateTimeInterface $delDate = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['base:read'])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['base:read'])]
    protected ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        if ($this->id === null) {
            $this->id = Uuid::uuid4()->toString(); // Генерируем UUID при создании
        }
    }

    // Lifecycle Callbacks
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

    public function getDelStatus(): DelStatusEnum
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

    // Метод для мягкого удаления
    public function softDelete(): void
    {
        $this->delStatus = DelStatusEnum::DELETED;
        $this->delDate = new \DateTime();
    }

    // Проверка, активна ли запись
    public function isActive(): bool
    {
        return $this->delStatus === DelStatusEnum::ACTIVE;
    }
}