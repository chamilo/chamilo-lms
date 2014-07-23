<?php

namespace ChamiloLMS\MessageBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Console\Application;
use ChamiloLMS\InstallerBundle\Command\InstallCommand;

/**
 * Class ChamiloLMSMessageBundle
 * @package ChamiloLMS\ChamiloLMSMessageBundle
 */
class ChamiloLMSMessageBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSMessageBundle';
    }
}
