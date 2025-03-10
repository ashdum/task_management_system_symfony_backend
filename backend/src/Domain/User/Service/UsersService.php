<?php
namespace App\Domain\User\Service;

use App\Domain\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsersService
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
    }

    public function getUser(string $id): ?User
    {
        return $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.id = :id')
            ->andWhere('u.delStatus = :active')
            ->setParameter('id', $id)
            ->setParameter('active', \App\Shared\Enum\DelStatusEnum::ACTIVE->value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getUserByEmail(string $email): ?User
    {
        return $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.email = :email')
            ->andWhere('u.delStatus = :active')
            ->setParameter('email', $email)
            ->setParameter('active', \App\Shared\Enum\DelStatusEnum::ACTIVE->value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function createUser(string $email, string $password, ?string $fullName = null, ?string $avatar = null): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setFullName($fullName);
        $user->setAvatar($avatar);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function updateUser(User $user, ?string $fullName = null, ?string $avatar = null): User
    {
        if ($fullName !== null) {
            $user->setFullName($fullName);
        }
        if ($avatar !== null) {
            $user->setAvatar($avatar);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function changePassword(User $user, string $newPassword): User
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function saveUser(User $user): void
    {
        $this->em->persist($user);
        $this->em->flush();
    }

    public function deleteUser(User $user): void
    {
        $user->softDelete();
        $this->em->persist($user);
        $this->em->flush();
    }
}