<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use App\Common\Validator\StrongPassword;

class RegisterDTO
{
	#[Assert\NotBlank(message: 'Email обязателен')]
	#[Assert\Email(message: 'Неверный формат email')]
	public string $email;

	#[Assert\NotBlank(message: 'Пароль обязателен')]
	#[StrongPassword]
	public string $password;

	#[Assert\Length(min: 2, max: 50, minMessage: 'Имя должно быть длиннее 2 символов', maxMessage: 'Имя должно быть короче 50 символов')]
	public ?string $fullName = null;

	public ?string $avatar = null;
}