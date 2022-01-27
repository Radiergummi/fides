<?php

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(flags: Attribute::TARGET_PROPERTY)]
class SecurityZoneIdentifier extends Constraint
{
    public string $badStartCharacterMessage = 'Security zone identifiers must begin with a letter (a-z).';

    public string $badTypeMessage = 'Security zone identifiers must be strings.';

    public string $lengthMessage = 'Security zone identifiers must shorter than 64 characters.';

    public string $message = 'Security zone identifiers must consist of letters, numbers, "-", or "_" exclusively.';

    public string $uppercaseMessage = 'Security zone identifiers must be lowercase.';

    public string $whitespaceMessage = 'Security zone identifiers may not contain whitespace characters.';
}
