<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

class UserGroup extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'usergroup';
    }

    public function getCategory(): string
    {
        return 'admin';
    }

    public function getIcon(): string
    {
        return 'mdi-xml';
    }

    public function getLink(): string
    {
        return '/resources/usergroup/';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'usergroups' => \Chamilo\CoreBundle\Entity\Usergroup::class,
        ];
    }
}
