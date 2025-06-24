<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

readonly class SettingsManagerHelper
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private AccessUrlHelper $accessUrlHelper,
    ) {}

    public function isOverridden(string $name, ?AccessUrl $accessUrl = null): bool
    {
        return null !== $this->getOverride($name, $accessUrl);
    }

    public function getOverride(string $name, ?AccessUrl $accessUrl = null): mixed
    {
        if (!$this->parameterBag->has('settings_overrides')) {
            return null;
        }

        $settingsOverrides = $this->parameterBag->get('settings_overrides');

        $accessUrl ??= $this->accessUrlHelper->getCurrent();

        if (isset($settingsOverrides[$accessUrl->getId()][$name])) {
            return $settingsOverrides[$accessUrl->getId()][$name];
        }

        if (isset($settingsOverrides['default'][$name])) {
            return $settingsOverrides['default'][$name];
        }

        return null;
    }
}
