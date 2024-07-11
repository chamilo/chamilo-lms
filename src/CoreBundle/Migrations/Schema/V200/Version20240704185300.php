<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use const DIRECTORY_SEPARATOR;

class Version20240704185300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Fix stylesheet and theme settings and move theme directory during development';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM settings WHERE variable IN ('stylesheets', 'theme')");

        $kernel = $this->container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $themeDirectory = $rootPath.'/var/theme';
        $themesDirectory = $rootPath.'/var/themes';

        $finder = new Finder();
        $filesystem = new Filesystem();

        if (!$filesystem->exists($themeDirectory)) {
            return;
        }

        $finder->directories()->in($themeDirectory)->depth('== 0');

        foreach ($finder as $entry) {
            if ($entry->isDir()) {
                $this->write(
                    sprintf(
                        'Moving theme directory: %s to %s',
                        $entry->getRealPath(),
                        $themesDirectory.DIRECTORY_SEPARATOR
                    )
                );
                $filesystem->rename(
                    $entry->getRealPath(),
                    $themesDirectory.DIRECTORY_SEPARATOR.$entry->getRelativePathname(),
                    true
                );
            }
        }

        $filesystem->remove($themeDirectory);
    }
}
