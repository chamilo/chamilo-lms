<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Finder\Finder;

final class Version20231110194300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Copy custom theme folder to assets and update webpack.config';
    }

    private function getDefaultThemeNames(): array
    {
        return [
            'academica',
            'chamilo',
            'chamilo_red',
            'cosmic_campus',
            'holi',
            'readable',
            'sober_brown',
            'baby_orange',
            'chamilo_electric_blue',
            'chamilo_sport_red',
            'delicious_bordeaux',
            'journal',
            'royal_purple',
            'spacelab',
            'beach',
            'chamilo_green',
            'cool_blue',
            'empire_green',
            'kiddy',
            'silver_line',
            'steel_grey',
            'blue_lagoon',
            'chamilo_orange',
            'corporate',
            'fruity_orange',
            'medical',
            'simplex',
            'tasty_olive',
        ];
    }

    public function up(Schema $schema): void
    {
        $kernel = $this->container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $defaulThemesFolders = $this->getDefaultThemeNames();

        $sourceDir = $rootPath.'/app/Resources/public/css/themes';

        if (!is_dir($sourceDir)) {
            return;
        }

        $filesystem = $this->container->get('oneup_flysystem.themes_filesystem');

        $finder = new Finder();
        $finder->directories()->in($sourceDir)->depth('== 0');

        foreach ($finder as $folder) {
            $themeFolderName = $folder->getRelativePathname();

            if (\in_array($themeFolderName, $defaulThemesFolders, true)) {
                continue;
            }

            if ($filesystem->directoryExists($themeFolderName)) {
                continue;
            }

            $filesystem->createDirectory($themeFolderName);

            $directory = (new Finder())->in($folder->getRealPath());

            foreach ($directory as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $newFileRelativePathname = $themeFolderName.DIRECTORY_SEPARATOR.$file->getRelativePathname();
                $fileContents = $file->getContents();
                $filesystem->write($newFileRelativePathname, $fileContents);
            }
        }
    }
}
