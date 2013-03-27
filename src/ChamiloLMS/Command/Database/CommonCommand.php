<?php

namespace ChamiloLMS\Command\Database;
use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;

class CommonCommand extends AbstractCommand
{
    /**
     * Gets the installation version path
     *
     * @param string $version
     *
     * @return string
     */
    public function getInstallationPath($version)
    {
        return api_get_path(SYS_PATH).'main/install/'.$version.'/';
    }

}