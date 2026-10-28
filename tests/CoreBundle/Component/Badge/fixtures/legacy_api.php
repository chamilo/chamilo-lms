<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Component\Badge;

use Chamilo\Tests\CoreBundle\Component\Badge\OpenBadge2Test;

// Override only this component's legacy configuration calls, without booting a portal.
function api_get_path($type): string
{
    return WEB_CODE_PATH === $type ? 'https://example.org/main/' : 'https://example.org/';
}

function api_get_current_access_url_id(): int
{
    return 1;
}

function api_get_setting($name): string
{
    return OpenBadge2Test::$settings[$name] ?? '';
}
