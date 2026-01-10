<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Symfony\Component\Finder\Finder;

readonly class PluginEntityLoaderHelper
{
    public function __construct(
        private string $pluginDir,
    ) {}

    public function getEntityDirectories(): array
    {
        $finder = new Finder();
        $finder->directories()->in($this->pluginDir)->name('Entity')->depth('<= 2');

        $directories = [];
        foreach ($finder as $dir) {
            $directories[] = $dir->getRealPath();
        }

        return $directories;
    }
}
