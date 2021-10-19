<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Framework;

use Chamilo\Kernel;

class LegacyConfigurationHelper
{
    private array $configuration;

    public function loadValues(Kernel $kernel): void
    {
        $_configuration = [];

        require_once $kernel->getConfigurationFile();

        $this->configuration = $_configuration;
    }

    public function getValue(string $variable)
    {
        // Check the current url id, id = 1 by default
        $urlId = isset($this->configuration['access_url']) ? (int) $this->configuration['access_url'] : 1;

        $variable = trim($variable);

        // Check if variable exists
        if (isset($this->configuration[$variable])) {
            if (\is_array($this->configuration[$variable])) {
                // Check if it exists for the sub portal
                if (\array_key_exists($urlId, $this->configuration[$variable])) {
                    return $this->configuration[$variable][$urlId];
                }
                // Try to found element with id = 1 (master portal)
                if (\array_key_exists(1, $this->configuration[$variable])) {
                    return $this->configuration[$variable][1];
                }
            }

            return $this->configuration[$variable];
        }

        return false;
    }
}
