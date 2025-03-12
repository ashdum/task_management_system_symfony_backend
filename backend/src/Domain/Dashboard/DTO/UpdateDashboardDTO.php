<?php

namespace App\Domain\Dashboard\DTO;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateDashboardDTO
{
    #[Assert\Length(max: 255, maxMessage: 'Title cannot exceed 255 characters')]
    #[Groups(['dashboard:write'])]
    public ?string $title = null;

    #[Assert\All([new Assert\Uuid(message: 'Each owner ID must be a valid UUID')])]
    #[Groups(['dashboard:write'])]
    public ?array $ownerIds = null;

    #[Assert\Length(max: 255, maxMessage: 'Background cannot exceed 255 characters')]
    #[Groups(['dashboard:write'])]
    public ?string $background = null;

    #[Groups(['dashboard:write'])]
    public ?string $description = null;

    #[Assert\Type(type: 'bool', message: 'isPublic must be a boolean')]
    #[Groups(['dashboard:write'])]
    public ?bool $isPublic = null;

    #[Groups(['dashboard:write'])]
    public ?array $settings = null;

    // Геттеры и сеттеры аналогичны CreateDashboardDTO

	/**
	 * @param string|null $title
	 */
	public function setTitle(?string $title): void
	{
		$this->title = $title;
	}

	/**
	 * @return string|null
	 */
	public function getTitle(): ?string
	{
		return $this->title;
	}

	/**
	 * @return array|null
	 */
	public function getOwnerIds(): ?array
	{
		return $this->ownerIds;
	}

	/**
	 * @param array|null $ownerIds
	 */
	public function setOwnerIds(?array $ownerIds): void
	{
		$this->ownerIds = $ownerIds;
	}

	/**
	 * @return string|null
	 */
	public function getBackground(): ?string
	{
		return $this->background;
	}

	/**
	 * @param string|null $background
	 */
	public function setBackground(?string $background): void
	{
		$this->background = $background;
	}

	/**
	 * @return string|null
	 */
	public function getDescription(): ?string
	{
		return $this->description;
	}

	/**
	 * @param string|null $description
	 */
	public function setDescription(?string $description): void
	{
		$this->description = $description;
	}

	/**
	 * @return bool|null
	 */
	public function getIsPublic(): ?bool
	{
		return $this->isPublic;
	}

	/**
	 * @param bool|null $isPublic
	 */
	public function setIsPublic(?bool $isPublic): void
	{
		$this->isPublic = $isPublic;
	}

	/**
	 * @return array|null
	 */
	public function getSettings(): ?array
	{
		return $this->settings;
	}

	/**
	 * @param array|null $settings
	 */
	public function setSettings(?array $settings): void
	{
		$this->settings = $settings;
	}
}