<?php

namespace App\Domain\Dashboard\Entity;

use App\Domain\Column\Entity\Column;
use App\Domain\Invitation\Entity\Invitation;
use App\Domain\User\Entity\User;
use App\Shared\Entity\BaseEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: 'App\Domain\Dashboard\Repository\DashboardRepository')]
#[ORM\Table(name: 'dashboards')]
class Dashboard extends BaseEntity
{
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['dashboard:read', 'dashboard:write'])]
    private string $title;

    #[ORM\Column(type: 'json')]
    #[Groups(['dashboard:read', 'dashboard:write'])]
    private array $ownerIds;

    #[ORM\OneToMany(mappedBy: 'dashboard', targetEntity: DashboardUser::class, cascade: ['persist', 'remove'])]
    #[Groups(['dashboard:read'])]
    private Collection $dashboardUsers;

    /* #[ORM\OneToMany(mappedBy: 'dashboard', targetEntity: Column::class, cascade: ['persist', 'remove'])]
    #[Groups(['dashboard:read'])]
    private Collection $columns; */

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['dashboard:read', 'dashboard:write'])]
    private ?string $background = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['dashboard:read', 'dashboard:write'])]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['dashboard:read', 'dashboard:write'])]
    private bool $isPublic = false;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['dashboard:read', 'dashboard:write'])]
    private ?array $settings = null;

    /* #[ORM\OneToMany(mappedBy: 'dashboard', targetEntity: Invitation::class, cascade: ['persist', 'remove'])]
    #[Groups(['dashboard:read'])]
    private Collection $invitations; */

    public function __construct()
    {
        parent::__construct();
        $this->dashboardUsers = new ArrayCollection();
       /*  $this->columns = new ArrayCollection(); */
        /* $this->invitations = new ArrayCollection(); */
    }

    // Геттеры и сеттеры
    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getOwnerIds(): array
    {
        return $this->ownerIds;
    }

    public function setOwnerIds(array $ownerIds): self
    {
        $this->ownerIds = $ownerIds;
        return $this;
    }

    public function getDashboardUsers(): Collection
    {
        return $this->dashboardUsers;
    }

    public function addDashboardUser(DashboardUser $dashboardUser): self
    {
        if (!$this->dashboardUsers->contains($dashboardUser)) {
            $this->dashboardUsers->add($dashboardUser);
            $dashboardUser->setDashboard($this);
        }
        return $this;
    }

    /* public function getColumns(): Collection
    {
        return $this->columns;
    } */

    public function getBackground(): ?string
    {
        return $this->background;
    }

    public function setBackground(?string $background): self
    {
        $this->background = $background;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function getSettings(): ?array
    {
        return $this->settings;
    }

    public function setSettings(?array $settings): self
    {
        $this->settings = $settings;
        return $this;
    }

   /*  public function getInvitations(): Collection
    {
        return $this->invitations;
    } */
}