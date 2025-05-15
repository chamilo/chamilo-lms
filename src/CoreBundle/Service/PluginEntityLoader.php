<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service;

use Symfony\Component\Finder\Finder;

readonly class PluginEntityLoader
{
    public function __construct(
        private string $pluginDir,
    ) {}

    public function getEntityDirectories(): array
    {
        $finder = new Finder();
        $finder->directories()->in($this->pluginDir)->name('Entity')->depth('== 1');

        $directories = [];
        foreach ($finder as $dir) {
            $directories[] = $dir->getRealPath();
        }

        return $directories;
    }
}
