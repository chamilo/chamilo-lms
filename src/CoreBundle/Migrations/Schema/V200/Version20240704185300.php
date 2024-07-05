<?php

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class Version20240704185300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return "Fix stylesheet and theme settings and move theme directory during development";
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM settings WHERE variable IN ('stylesheets', 'theme')");

        $kernel = $this->container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $themeDirectory = $rootPath.'/var/theme';
        $themesDirectory = $rootPath.'/var/themes';

        $finder = new Finder();
        $filesystem = new Filesystem();

        $finder->directories()->in($themeDirectory)->depth('== 0');

        foreach ($finder as $entry) {
            if ($entry->isDir()) {
                error_log(
                    sprintf(
                        "Moving theme directory: %s %s",
                        $entry->getRealPath(),
                        $themesDirectory.'/'
                    )
                );
                $filesystem->rename($entry->getRealPath(), $themesDirectory.'/'.$entry->getRelativePathname());
            }
        }
    }
}