<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Toolbox;

use Symfony\Component\Serializer\Attribute\Groups;

final class ToolboxVisibilityInput
{
    #[Groups(['toolbox_visibility:write'])]
    public bool $visible = false;
}
