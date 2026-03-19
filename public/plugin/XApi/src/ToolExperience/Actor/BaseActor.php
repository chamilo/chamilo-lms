<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Actor;

/**
 * Class BaseActor.
 */
abstract class BaseActor
{
    /**
     * Build a plain xAPI actor payload.
     */
    abstract public function generate(): array;

    protected function normalizeString(?string $value): string
    {
        return trim((string) $value);
    }
}
