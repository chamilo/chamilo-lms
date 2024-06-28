<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Filesystem\Filesystem;
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
        $destinationDir = $rootPath.'/assets/css/themes/';
        $chamiloDefaultCssPath = $destinationDir.'chamilo/default.css';

        if (!is_dir($sourceDir)) {
            return;
        }

        $finder = new Finder();
        $finder->directories()->in($sourceDir)->depth('== 0');
        $newThemes = [];
        foreach ($finder as $folder) {
            $folderName = $folder->getRelativePathname();

            if (!\in_array($folderName, $defaulThemesFolders, true)) {
                $sourcePath = $folder->getRealPath();
                $destinationPath = $destinationDir.$folderName;

                if (!file_exists($destinationPath)) {
                    $this->copyDirectory($sourcePath, $destinationPath);
                    $newThemes[] = $folderName;

                    if (file_exists($chamiloDefaultCssPath)) {
                        $newThemeDefaultCssPath = $destinationPath.'/default.css';
                        copy($chamiloDefaultCssPath, $newThemeDefaultCssPath);
                    }
                }
            }
        }

        $this->updateWebpackConfig($rootPath, $newThemes);
    }

    private function copyDirectory($src, $dst): void
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (('.' !== $file) && ('..' !== $file)) {
                if (is_dir($src.'/'.$file)) {
                    $this->copyDirectory($src.'/'.$file, $dst.'/'.$file);
                } else {
                    copy($src.'/'.$file, $dst.'/'.$file);
                }
            }
        }
        closedir($dir);
    }

    private function updateWebpackConfig(string $rootPath, array $newThemes): void
    {
        $webpackConfigPath = $rootPath.'/webpack.config.js';

        if (!file_exists($webpackConfigPath)) {
            return;
        }

        $content = file_get_contents($webpackConfigPath);
        $pattern = '/(const themes = \\[\\s*")([^"\\]]+)("\\s*\\])/';
        $replacement = function ($matches) use ($newThemes) {
            $existingThemes = explode('", "', trim($matches[2], '"'));
            $allThemes = array_unique(array_merge($existingThemes, $newThemes));
            $newThemesString = implode('", "', $allThemes);

            return $matches[1].$newThemesString.$matches[3];
        };

        $newContent = preg_replace_callback($pattern, $replacement, $content);

        file_put_contents($webpackConfigPath, $newContent);
    }
}
