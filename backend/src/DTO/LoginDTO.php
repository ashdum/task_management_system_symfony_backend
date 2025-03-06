<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class LoginDTO
{
	#[Assert\NotBlank(message: 'Email обязателен')]
	#[Assert\Email(message: 'Неверный формат email')]
	public string $email;

	#[Assert\NotBlank(message: 'Пароль обязателен')]
	#[Assert\Length(min: 6, minMessage: 'Пароль должен быть длиннее 6 символов')]
	public string $password;
}