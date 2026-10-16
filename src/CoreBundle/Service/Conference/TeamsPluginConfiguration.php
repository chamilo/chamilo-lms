<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Conference;

use Chamilo\CoreBundle\Helpers\PluginHelper;

final readonly class TeamsPluginConfiguration implements TeamsPluginConfigurationInterface
{
    private const string PLUGIN_NAME = 'Teams';

    public function __construct(
        private PluginHelper $pluginHelper
    ) {}

    public function isEnabled(): bool
    {
        return $this->pluginHelper->isPluginEnabled(self::PLUGIN_NAME);
    }

    public function isConfigured(): bool
    {
        return $this->isEnabled()
            && '' !== $this->getTenantId()
            && '' !== $this->getClientId()
            && '' !== $this->getClientSecret();
    }

    public function getTenantId(): string
    {
        return $this->getString('tenantId');
    }

    public function getClientId(): string
    {
        return $this->getString('clientId');
    }

    public function getClientSecret(): string
    {
        return $this->getString('clientSecret');
    }

    public function isPersonalConferenceEnabled(): bool
    {
        return $this->getBoolean('enablePersonalConference');
    }

    public function isGlobalConferenceEnabled(): bool
    {
        return $this->getBoolean('enableGlobalConference');
    }

    private function getString(string $key): string
    {
        return trim((string) $this->pluginHelper->getPluginSetting(self::PLUGIN_NAME, $key));
    }

    private function getBoolean(string $key): bool
    {
        $value = $this->pluginHelper->getPluginSetting(self::PLUGIN_NAME, $key);

        return true === $value
            || 1 === $value
            || '1' === $value
            || \in_array(strtolower(trim((string) $value)), ['true', 'yes', 'on'], true);
    }
}
