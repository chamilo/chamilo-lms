<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sensio\Bundle\DistributionBundle\Composer;

use Symfony\Component\ClassLoader\ClassCollectionLoader;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;
use Composer\Script\CommandEvent;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class ScriptHandler
{
    /**
     * Builds the bootstrap file.
     *
     * The bootstrap file contains PHP file that are always needed by the application.
     * It speeds up the application bootstrapping.
     *
     * @param $event CommandEvent A instance
     */
    public static function buildBootstrap(CommandEvent $event)
    {
        $options = self::getOptions($event);
        $appDir = $options['symfony-app-dir'];

        if (!is_dir($appDir)) {
            echo 'The symfony-app-dir ('.$appDir.') specified in composer.json was not found in '.getcwd().', can not build bootstrap file.'.PHP_EOL;

            return;
        }

        static::executeBuildBootstrap($appDir, $options['process-timeout']);
    }

    /**
     * Clears the Symfony cache.
     *
     * @param $event CommandEvent A instance
     */
    public static function clearCache(CommandEvent $event)
    {
        $options = self::getOptions($event);
        $appDir = $options['symfony-app-dir'];

        if (!is_dir($appDir)) {
            echo 'The symfony-app-dir ('.$appDir.') specified in composer.json was not found in '.getcwd().', can not clear the cache.'.PHP_EOL;

            return;
        }

        static::executeCommand($event, $appDir, 'cache:clear --no-warmup', $options['process-timeout']);
    }

    /**
     * Installs the assets under the web root directory.
     *
     * For better interoperability, assets are copied instead of symlinked by default.
     *
     * Even if symlinks work on Windows, this is only true on Windows Vista and later,
     * but then, only when running the console with admin rights or when disabling the
     * strict user permission checks (which can be done on Windows 7 but not on Windows
     * Vista).
     *
     * @param $event CommandEvent A instance
     */
    public static function installAssets(CommandEvent $event)
    {
        $options = self::getOptions($event);
        $appDir = $options['symfony-app-dir'];
        $webDir = $options['symfony-web-dir'];

        $symlink = '';
        if ($options['symfony-assets-install'] == 'symlink') {
            $symlink = '--symlink ';
        } elseif ($options['symfony-assets-install'] == 'relative') {
            $symlink = '--symlink --relative ';
        }

        if (!is_dir($webDir)) {
            echo 'The symfony-web-dir ('.$webDir.') specified in composer.json was not found in '.getcwd().', can not install assets.'.PHP_EOL;

            return;
        }

        static::executeCommand($event, $appDir, 'assets:install '.$symlink.escapeshellarg($webDir));
    }

    /**
     * Updated the requirements file.
     *
     * @param $event CommandEvent A instance
     */
    public static function installRequirementsFile(CommandEvent $event)
    {
        $options = self::getOptions($event);
        $appDir = $options['symfony-app-dir'];

        if (!is_dir($appDir)) {
            echo 'The symfony-app-dir ('.$appDir.') specified in composer.json was not found in '.getcwd().', can not install the requirements file.'.PHP_EOL;

            return;
        }

        copy(__DIR__.'/../Resources/skeleton/app/SymfonyRequirements.php', $appDir.'/SymfonyRequirements.php');
        copy(__DIR__.'/../Resources/skeleton/app/check.php', $appDir.'/check.php');

        $webDir = $options['symfony-web-dir'];

        // if the user has already removed the config.php file, do nothing
        // as the file must be removed for production use
        if (is_file($webDir.'/config.php')) {
            copy(__DIR__.'/../Resources/skeleton/web/config.php', $webDir.'/config.php');
        }
    }

    public static function doBuildBootstrap($appDir)
    {
        $file = $appDir.'/bootstrap.php.cache';
        if (file_exists($file)) {
            unlink($file);
        }

        $classes = array(
            'Symfony\\Component\\HttpFoundation\\ParameterBag',
            'Symfony\\Component\\HttpFoundation\\HeaderBag',
            'Symfony\\Component\\HttpFoundation\\FileBag',
            'Symfony\\Component\\HttpFoundation\\ServerBag',
            'Symfony\\Component\\HttpFoundation\\Request',
            'Symfony\\Component\\HttpFoundation\\Response',
            'Symfony\\Component\\HttpFoundation\\ResponseHeaderBag',

            'Symfony\\Component\\DependencyInjection\\ContainerAwareInterface',
            // Cannot be included because annotations will parse the big compiled class file
            //'Symfony\\Component\\DependencyInjection\\ContainerAware',
            'Symfony\\Component\\DependencyInjection\\Container',
            'Symfony\\Component\\HttpKernel\\Kernel',
            'Symfony\\Component\\ClassLoader\\ClassCollectionLoader',
            'Symfony\\Component\\ClassLoader\\ApcClassLoader',
            'Symfony\\Component\\HttpKernel\\Bundle\\Bundle',
            'Symfony\\Component\\Config\\ConfigCache',
            // cannot be included as commands are discovered based on the path to this class via Reflection
            //'Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle',
        );

        // introspect the autoloader to get the right file
        // we cannot use class_exist() here as it would load the class
        // which won't be included into the cache then.
        // we know that composer autoloader is first (see bin/build_bootstrap.php)
        $autoloaders = spl_autoload_functions();
        if ($autoloaders[0][0]->findFile('Symfony\\Bundle\\FrameworkBundle\\HttpKernel')) {
            $classes[] = 'Symfony\\Bundle\\FrameworkBundle\\HttpKernel';
        } else {
            $classes[] = 'Symfony\\Component\\HttpKernel\\DependencyInjection\\ContainerAwareHttpKernel';
        }

        ClassCollectionLoader::load($classes, dirname($file), basename($file, '.php.cache'), false, false, '.php.cache');

        file_put_contents($file, sprintf("<?php

namespace { \$loader = require_once __DIR__.'/autoload.php'; }

%s

namespace { return \$loader; }
            ", substr(file_get_contents($file), 5)));
    }

    protected static function executeCommand(CommandEvent $event, $appDir, $cmd, $timeout = 300)
    {
        $php = escapeshellarg(self::getPhp());
        $console = escapeshellarg($appDir.'/console');
        if ($event->getIO()->isDecorated()) {
            $console .= ' --ansi';
        }

        $process = new Process($php.' '.$console.' '.$cmd, null, null, null, $timeout);
        $process->run(function ($type, $buffer) { echo $buffer; });
        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('An error occurred when executing the "%s" command.', escapeshellarg($cmd)));
        }
    }

    protected static function executeBuildBootstrap($appDir, $timeout = 300)
    {
        $php = escapeshellarg(self::getPhp());
        $cmd = escapeshellarg(__DIR__.'/../Resources/bin/build_bootstrap.php');
        $appDir = escapeshellarg($appDir);

        $process = new Process($php.' '.$cmd.' '.$appDir, null, null, null, $timeout);
        $process->run(function ($type, $buffer) { echo $buffer; });
        if (!$process->isSuccessful()) {
            throw new \RuntimeException('An error occurred when generating the bootstrap file.');
        }
    }

    protected static function getOptions(CommandEvent $event)
    {
        $options = array_merge(array(
            'symfony-app-dir' => 'app',
            'symfony-web-dir' => 'web',
            'symfony-assets-install' => 'hard'
        ), $event->getComposer()->getPackage()->getExtra());

        $options['symfony-assets-install'] = getenv('SYMFONY_ASSETS_INSTALL') ?: $options['symfony-assets-install'];

        $options['process-timeout'] = $event->getComposer()->getConfig()->get('process-timeout');

        return $options;
    }

    protected static function getPhp()
    {
        $phpFinder = new PhpExecutableFinder;
        if (!$phpPath = $phpFinder->find()) {
            throw new \RuntimeException('The php executable could not be found, add it to your PATH environment variable and try again');
        }

        return $phpPath;
    }
}
