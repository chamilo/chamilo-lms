<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Toolbox;

use Symfony\Component\Serializer\Attribute\Groups;

final class ToolboxGenerateInput
{
    #[Groups(['toolbox_generate:write'])]
    public string $prompt = '';

    #[Groups(['toolbox_generate:write'])]
    public ?string $provider = null;
}
