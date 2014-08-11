<?php

namespace ChamiloLMS\SettingsBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ChamiloLMSSettingsBundle extends Bundle
{
    public function getParent()
    {
        return 'SyliusSettingsBundle';
    }
}
