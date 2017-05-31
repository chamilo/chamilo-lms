<?php

namespace Chash\Command\Info;

use Chash\Command\Database\CommonDatabaseCommand;
use Chash\Command\Installation\CommonCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Console\Helper\Table;

/**
 * Class GetInstancesCommand
 * @package Chash\Command\Translation
 */
class GetInstancesCommand extends CommonCommand
{
    protected function configure()
    {
        $this
            ->setName('info:get_instances')
            ->setDescription('Get chamilo instances info')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                '/var/www/'
            )
            ->addArgument(
                'folder',
                InputArgument::OPTIONAL,
                'www'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');
        $folderInsidePath = $input->getArgument('folder');

        $output->writeln("Checking chamilo portals here: $path");
        $fs = new Filesystem();
        $finder = new Finder();
        $dirs = $finder->directories()->in($path)->depth('== 0');
        $portals = [];
        if (!empty($folderInsidePath)) {
            $output->writeln("Checking chamilo portals inside subfolder: $folderInsidePath");
            $folderInsidePath = '/'.$folderInsidePath;
        }

        /** @var SplFileInfo $dir */
        foreach ($dirs as $dir) {
            $appPath = $dir->getRealPath().$folderInsidePath;
            $configurationFile = $appPath.'/app/config/configuration.php';
            if ($fs->exists($configurationFile)) {
                $portal = $this->getPortalInfoFromConfiguration($configurationFile);
            } else {
                $configurationFile = $appPath.'/main/inc/conf/configuration.php';
                $portal = $this->getPortalInfoFromConfiguration($configurationFile);
            }

            if (!empty($portal)) {
                $portals[] = $portal;
            }
        }

        $table = new Table($output);
        $table
            ->setHeaders(array('Portal', 'Version', 'Packager', 'Configuration file'))
            ->setRows($portals)
        ;

        $table->render();

        return null;
    }

    /**
     * @param string $configurationFile
     * @return array
     */
    public function getPortalInfoFromConfiguration($configurationFile)
    {
        $fs = new Filesystem();

        if ($fs->exists($configurationFile)) {
            $lines = file($configurationFile, FILE_IGNORE_NEW_LINES);
            $version = '';
            $url = '';
            $packager = '';
            foreach ($lines as $line) {
                if (strpos($line, 'system_version') !== false) {
                    $replace = [
                        "\$_configuration['system_version']",
                        '=',
                        ';',
                        "'",
                    ];
                    $version = str_replace($replace, '', $line);
                }
                if (strpos($line, 'root_web') !== false) {
                    $replace = [
                        "\$_configuration['root_web']",
                        '=',
                        ';',
                        "'",
                    ];
                    $url = str_replace($replace, '', $line);
                }
                if (strpos($line, 'packager') !== false) {
                    $replace = [
                        "\$_configuration['packager']",
                        '=',
                        ';',
                        "'",
                        '//'
                    ];
                    $packager = str_replace($replace, '', $line);
                }
            }
            $portal = [$url, $version, $packager, $configurationFile];
            $portal = array_map('trim', $portal);

            return $portal;
        }
        return [];
    }
}
