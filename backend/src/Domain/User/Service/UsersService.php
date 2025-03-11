<?php
namespace App\Domain\User\Service;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsersService
{
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
    }

    public function getUser(string $id): ?User
    {
        return $this->userRepository->getActiveById($id);
    }

    public function getUserByEmail(string $email): ?User
    {
        return $this->userRepository->getActiveByEmail($email);
    }

    public function createUser(string $email, string $password, ?string $fullName = null, ?string $avatar = null): User
    {
        $user = new User();
        $user->setEmail($email)
            ->setPassword($this->passwordHasher->hashPassword($user, $password))
            ->setFullName($fullName)
            ->setAvatar($avatar);

        $this->userRepository->save($user);
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

        $this->userRepository->save($user);
        return $user;
    }

    public function changePassword(User $user, string $newPassword): User
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
        $this->userRepository->save($user);
        return $user;
    }

    public function saveUser(User $user): void
    {
        $this->userRepository->save($user);
    }

    public function deleteUser(User $user): void
    {
        $this->userRepository->delete($user);
    }
}