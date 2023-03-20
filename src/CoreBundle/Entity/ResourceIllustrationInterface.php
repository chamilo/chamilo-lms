<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

interface ResourceIllustrationInterface
{
    public function getResourceNode(): ?ResourceNode;

    public function getDefaultIllustration(int $size): string;
}
