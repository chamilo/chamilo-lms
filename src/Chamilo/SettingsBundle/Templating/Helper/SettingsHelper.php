<?php

namespace Chamilo\SettingsBundle\Templating\Helper;

use Sylius\Bundle\SettingsBundle\Templating\Helper\SettingsHelper as
    SyliusHelper;

class SettingsHelper extends SyliusHelper
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'chamilo_settings';
    }
}
