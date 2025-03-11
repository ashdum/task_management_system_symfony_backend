<?php

namespace App\Domain\User\Repository;

use App\Domain\User\Entity\User;
use App\Shared\Enum\DelStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Репозиторий для работы с сущностью User.
 *
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Создает базовый QueryBuilder с фильтром активных пользователей.
     *
     * @param string $alias Алиас для таблицы
     * @return QueryBuilder
     */
    private function createActiveUsersQueryBuilder(string $alias = 'u'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->andWhere("$alias.delStatus = :active")
            ->setParameter('active', DelStatusEnum::ACTIVE);
    }

    /**
     * Находит активного пользователя по ID.
     *
     * @param string $id Идентификатор пользователя
     * @return User|null Пользователь или null, если не найден
     */
    public function getActiveById(string $id): ?User
    {
        return $this->createActiveUsersQueryBuilder()
            ->andWhere('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->setCacheable(true)
            ->getOneOrNullResult();
    }

    /**
     * Находит активного пользователя по email.
     *
     * @param string $email Email пользователя
     * @return User|null Пользователь или null, если не найден
     */
    public function getActiveByEmail(string $email): ?User
    {
        return $this->createActiveUsersQueryBuilder()
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->setCacheable(true)
            ->getOneOrNullResult();
    }

    /**
     * Сохраняет пользователя в базе данных.
     *
     * @param User $user Пользователь для сохранения
     */
    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Выполняет мягкое удаление пользователя.
     *
     * @param User $user Пользователь для удаления
     * @throws \LogicException Если пользователь не управляется Doctrine
     */
    public function softDelete(User $user): void
    {
        if (!$this->getEntityManager()->contains($user)) {
            throw new \LogicException('Пользователь не найден в базе данных');
        }
        $user->softDelete(); 
        $this->save($user);
    }
}