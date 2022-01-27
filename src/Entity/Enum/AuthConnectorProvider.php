<?php

/**
 * This file is part of fides, a Matchory application.
 *
 * Unauthorized copying of this file, via any medium, is strictly prohibited.
 * Its contents are strictly confidential and proprietary.
 *
 * @copyright 2020–2022 Matchory GmbH · All rights reserved
 * @author    Moritz Friedrich <moritz@matchory.com>
 */

declare(strict_types=1);

namespace App\Entity\Enum;

enum AuthConnectorProvider: string
{
    case Google = 'google';

    case Microsoft = 'microsoft';

    case Custom = 'custom';

    case GitHub = 'github';

    case Okta = 'okta';
}
