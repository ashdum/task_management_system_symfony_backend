<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsersService
{
	private EntityManagerInterface $em;
	private UserPasswordHasherInterface $passwordHashes;

	public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHashes)
	{
		$this->em = $em;
		$this->passwordHashes = $passwordHashes;
	}

	function validateUser(string $email, string $password): ?User
	{
		$user = $this->em->getRepository(User::class)
			->createQueryBuilder('u')
			->where('u.email = :email')
			->setParameter('email', $email)
			->getQuery()
			->getOneOrNullResult();

		if($user && $this->passwordHashes->isPasswordValid($user, $password)){
			return $user;
		}

		return null;
	}

}