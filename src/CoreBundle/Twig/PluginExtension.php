<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Twig;

use Chamilo\CoreBundle\Helpers\PluginHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class PluginExtension extends AbstractExtension
{
    /**
     * @var array<string, bool>
     */
    private array $enabledCache = [];

    public function __construct(
        private readonly PluginHelper $pluginHelper,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('chamilo_plugin_is_enabled', [$this, 'isPluginEnabled']),
        ];
    }

    public function isPluginEnabled(string $pluginName): bool
    {
        if (isset($this->enabledCache[$pluginName])) {
            return $this->enabledCache[$pluginName];
        }

        $this->enabledCache[$pluginName] = $this->pluginHelper->isPluginEnabled($pluginName);

        return $this->enabledCache[$pluginName];
    }
}
