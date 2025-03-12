<?php

namespace App\Domain\Dashboard\DTO;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class CreateDashboardDTO
{
    #[Assert\NotBlank(message: 'Title is required')]
    #[Assert\Length(max: 255, maxMessage: 'Title cannot exceed 255 characters')]
    #[Groups(['dashboard:write'])]
    public string $title;

    #[Assert\NotNull(message: 'Owner IDs are required')]
    #[Assert\All([new Assert\Uuid(message: 'Each owner ID must be a valid UUID')])]
    #[Groups(['dashboard:write'])]
    public array $ownerIds;

    #[Assert\Length(max: 255, maxMessage: 'Background cannot exceed 255 characters')]
    #[Groups(['dashboard:write'])]
    public ?string $background = null;

    #[Groups(['dashboard:write'])]
    public ?string $description = null;

    #[Assert\Type(type: 'bool', message: 'isPublic must be a boolean')]
    #[Groups(['dashboard:write'])]
    public ?bool $isPublic = false;

    #[Groups(['dashboard:write'])]
    public ?array $settings = null;

    // Геттеры и сеттеры
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }
    public function getOwnerIds(): array { return $this->ownerIds; }
    public function setOwnerIds(array $ownerIds): self { $this->ownerIds = $ownerIds; return $this; }
    public function getBackground(): ?string { return $this->background; }
    public function setBackground(?string $background): self { $this->background = $background; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function isPublic(): ?bool { return $this->isPublic; }
    public function setIsPublic(?bool $isPublic): self { $this->isPublic = $isPublic; return $this; }
    public function getSettings(): ?array { return $this->settings; }
    public function setSettings(?array $settings): self { $this->settings = $settings; return $this; }
}