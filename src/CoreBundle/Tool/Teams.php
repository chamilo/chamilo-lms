<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

final class Teams extends AbstractPlugin
{
    public function getTitle(): string
    {
        return 'Teams';
    }

    public function getTitleToShow(): string
    {
        return 'Microsoft Teams';
    }

    public function getIcon(): string
    {
        return 'mdi-video';
    }

    public function getLink(): string
    {
        return '/conference/teams/course';
    }

    public function getResourceTypes(): ?array
    {
        return [];
    }
}
