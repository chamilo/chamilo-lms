<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

use Symfony\Component\Serializer\Annotation\Groups;

abstract class AbstractResource
{
    #[Groups([
        'ctool:read',
    ])]
    public ?string $illustrationUrl = null;
}
