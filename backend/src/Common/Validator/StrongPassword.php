<?php
namespace App\Common\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class StrongPassword extends Constraint
{
    public string $message = 'Пароль должен содержать минимум 8 символов, одну заглавную букву, одну строчную, одну цифру и один специальный символ';

    public function validatedBy(): string
    {
        return StrongPasswordValidator::class;
    }
}

class StrongPasswordValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof StrongPassword) {
            throw new UnexpectedTypeException($constraint, StrongPassword::class);
        }

        if (null === $value || '' === $value) {
            return; // Пустые значения обрабатываются NotBlank
        }

        if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}$/', $value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}