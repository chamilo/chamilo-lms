<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests;

use Chamilo\CoreBundle\Framework\Container;
use PHPUnit\Runner\AfterTestHook;

/**
 * Container::getSession() prefers the static session over the one of the request, so a session
 * set by a test would leak into every later test.
 */
final class ResetLegacySessionExtension implements AfterTestHook
{
    public function executeAfterTest(string $test, float $time): void
    {
        Container::$session = null;
    }
}
