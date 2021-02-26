<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

/**
 * Interface ToolInterface.
 */
interface ToolInterface
{
    public function getName(): string;

    public function getLink(): string;

    public function getTarget(): string;

    public function getCategory(): string;
}
