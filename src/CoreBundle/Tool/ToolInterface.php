<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

interface ToolInterface
{
    public function getName(): string;

    public function getCategory(): string;

    public function getLink(): string;

    public function getIcon(): string;
}
