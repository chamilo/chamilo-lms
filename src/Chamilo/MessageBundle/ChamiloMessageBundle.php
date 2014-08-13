<?php

namespace Chamilo\MessageBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Console\Application;
use Chamilo\InstallerBundle\Command\InstallCommand;

/**
 * Class ChamiloMessageBundle
 * @package Chamilo\ChamiloMessageBundle
 */
class ChamiloMessageBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSMessageBundle';
    }
}
