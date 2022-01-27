<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use function is_string;
use function preg_match;
use function strlen;

class SecurityZoneIdentifierValidator extends ConstraintValidator
{
    /**
     * @param mixed      $value
     * @param Constraint $constraint
     *
     * @throws UnexpectedTypeException
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ( ! $constraint instanceof SecurityZoneIdentifier) {
            throw new UnexpectedTypeException(
                $value,
                PublicKey::class
            );
        }

        if ($value === null || $value === '') {
            return;
        }

        if ( ! is_string($value)) {
            $this->context
                ->buildViolation($constraint->badTypeMessage)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }

        if (strlen($value) > 63) {
            $this->context
                ->buildViolation($constraint->lengthMessage)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }

        if (preg_match('/[A-Z]/', $value)) {
            $this->context
                ->buildViolation($constraint->uppercaseMessage)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }

        if (preg_match('/\s/', $value)) {
            $this->context
                ->buildViolation($constraint->whitespaceMessage)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }

        if ( ! preg_match('/^[a-z]/', $value)) {
            $this->context
                ->buildViolation($constraint->badStartCharacterMessage)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }

        if ( ! preg_match('/^([a-z0-9_-]+)$/', $value)) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
