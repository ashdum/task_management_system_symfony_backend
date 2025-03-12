<?php

namespace App\Domain\Dashboard\Entity;

use App\Domain\User\Entity\User;
use App\Shared\Enum\DelStatusEnum;
use App\Shared\Enum\RoleEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'dashboard_users')]
#[ORM\Index(name: 'idx_dashboard_users_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_dashboard_users_dashboard_id', columns: ['dashboard_id'])]
class DashboardUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['dashboard:read'])]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'dashboardUsers')]
    #[ORM\JoinColumn(name: 'user_id', nullable: false)]
    #[Groups(['dashboard:read'])]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Dashboard::class, inversedBy: 'dashboardUsers')]
    #[ORM\JoinColumn(name: 'dashboard_id', nullable: false)]
    private Dashboard $dashboard;

    #[ORM\Column(type: 'string', enumType: RoleEnum::class)]
    #[Groups(['dashboard:read'])]
    private RoleEnum $role;

    #[ORM\Column(type: 'string', enumType: DelStatusEnum::class)]
    private DelStatusEnum $delStatus = DelStatusEnum::ACTIVE;

    public function __construct(string $id, User $user, Dashboard $dashboard, RoleEnum $role)
    {
        $this->id = $id;
        $this->user = $user;
        $this->dashboard = $dashboard;
        $this->role = $role;
    }

    public function getId(): string { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function getDashboard(): Dashboard { return $this->dashboard; }
    public function setDashboard(Dashboard $dashboard): self { $this->dashboard = $dashboard; return $this; }
    public function getRole(): RoleEnum { return $this->role; }
    public function setRole(RoleEnum $role): self { $this->role = $role; return $this; }
    public function getDelStatus(): DelStatusEnum { return $this->delStatus; }
    public function setDelStatus(DelStatusEnum $delStatus): self { $this->delStatus = $delStatus; return $this; }
}