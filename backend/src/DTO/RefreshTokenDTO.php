<?php

namespace App\DTO;
use Symfony\Component\Validator\Constraints as Assert;

class RefreshTokenDTO
{
	#[Assert\NotBlank(message: 'Refresh-токен обязателен')]
	public string $refreshToken;

}