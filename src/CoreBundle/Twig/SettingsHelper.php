<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Twig;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Sylius\Bundle\SettingsBundle\Templating\Helper\SettingsHelper as SylusSettingsHelper;

class SettingsHelper extends SylusSettingsHelper
{
    public function getName(): string
    {
        return 'chamilo_settings';
    }

    /**
     * @param string $parameter Example: `platform.setting`
     */
    public function getSettingsParameter(string $parameter)
    {
        return $this->settingsManager instanceof SettingsManager ? $this->settingsManager->getSetting($parameter, true) : '';
    }
}
