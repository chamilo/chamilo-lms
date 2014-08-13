<?php

namespace Chamilo\SettingsBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ChamiloSettingsBundle extends Bundle
{
    public function getParent()
    {
        return 'SyliusSettingsBundle';
    }
}
