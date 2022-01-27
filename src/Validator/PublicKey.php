<?php

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(flags: Attribute::TARGET_PROPERTY)]
class PublicKey extends Constraint
{
    public string $illegalFormatMessage = 'Illegal key format: {{ format }}';

    public string $message = 'Not a valid public key: {{ detail }}';
}
