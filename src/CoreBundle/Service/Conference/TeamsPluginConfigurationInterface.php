<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Conference;

interface TeamsPluginConfigurationInterface
{
    public function isEnabled(): bool;

    public function isConfigured(): bool;

    public function getTenantId(): string;

    public function getClientId(): string;

    public function getClientSecret(): string;

    public function isPersonalConferenceEnabled(): bool;

    public function isGlobalConferenceEnabled(): bool;
}
