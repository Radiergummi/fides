<?php

namespace App\Validator;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Exception\NoKeyLoadedException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class PublicKeyValidator extends ConstraintValidator
{
    /**
     * @param mixed      $value
     * @param Constraint $constraint
     *
     * @throws UnexpectedTypeException
     * @throws NoKeyLoadedException
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ( ! $constraint instanceof PublicKey) {
            throw new UnexpectedTypeException(
                $value,
                PublicKey::class
            );
        }

        if ($value === null || $value === '') {
            return;
        }

        if ( ! is_string($value)) {
            throw new UnexpectedValueException(
                $value,
                'string'
            );
        }

        try {
            $key = PublicKeyLoader::load($value);
        } catch (NoKeyLoadedException $exception) {
            $this->context
                ->buildViolation($constraint->message)
                ->setInvalidValue($value)
                ->setCause($exception)
                ->setParameter('{{ detail }}', $exception->getMessage())
                ->addViolation();

            return;
        }

        if (($format = $key->getLoadedFormat()) !== 'OpenSSH') {
            $this->context
                ->buildViolation($constraint->illegalFormatMessage)
                ->setParameter('{{ format }}', $format)
                ->addViolation();
        }

        // TODO: Should we validate the comment or hash?
        # $key->getHash();
        # $key->getComment();
        # $this->context
        #     ->buildViolation($constraint->message)
        #     ->setParameter('{{ detail }}', $value)
        #     ->addViolation();
    }
}
