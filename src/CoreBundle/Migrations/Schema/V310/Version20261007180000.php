<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Finder\Finder;

final class Version20261007180000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Copy the PENS packages from var/plugins/pens to the plugins filesystem.';
    }

    public function up(Schema $schema): void
    {
        $sourceDir = $this->container->get('kernel')->getProjectDir().'/var/plugins/pens';

        if (!is_dir($sourceDir)) {
            return;
        }

        $filesystem = $this->container->get('oneup_flysystem.plugins_filesystem');

        $finder = (new Finder())->files()->in($sourceDir)->depth('== 0');

        foreach ($finder as $file) {
            // plugin_pens.package_name keeps the bare filename, so the name must not change.
            $targetPath = 'Pens/'.$file->getFilename();

            if ($filesystem->fileExists($targetPath)) {
                continue;
            }

            $stream = fopen($file->getRealPath(), 'rb');

            if (false === $stream) {
                $this->write('Unable to read '.$file->getRealPath());

                continue;
            }

            try {
                $filesystem->writeStream($targetPath, $stream);
            } finally {
                fclose($stream);
            }
        }
    }

    public function down(Schema $schema): void {}
}
