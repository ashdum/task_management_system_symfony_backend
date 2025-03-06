<?php
namespace App\Common\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class StrongPasswordValidator extends ConstraintValidator
{
	public function validate($value, Constraint $constraint)
	{
		if (!$value) {
			$this->context->buildViolation('Пароль обязателен')->addViolation();
			return;
		}

		if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}$/', $value)) {
			$this->context->buildViolation('Пароль должен содержать минимум 8 символов, одну заглавную букву, одну строчную, одну цифру и один специальный символ')->addViolation();
		}
	}
}

class StrongPassword extends Constraint
{
	public $message = 'Пароль не соответствует требованиям';
}