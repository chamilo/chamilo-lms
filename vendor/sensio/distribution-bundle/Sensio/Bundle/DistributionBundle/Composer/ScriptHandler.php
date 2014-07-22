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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;
use Composer\Script\CommandEvent;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class ScriptHandler
{
    /**
     * Composer variables are declared static so that an event could update
     * a composer.json and set new options, making them immediately available
     * to forthcoming listeners.
     */
    private static $options = array(
        'symfony-app-dir' => 'app',
        'symfony-web-dir' => 'web',
        'symfony-assets-install' => 'hard',
        'symfony-cache-warmup' => false,
    );

    /**
     * Asks if the new directory structure should be used, installs the structure if needed.
     *
     * @param CommandEvent $event
     */
    public static function defineDirectoryStructure(CommandEvent $event)
    {
        $options = self::getOptions($event);

        if (!getenv('SENSIOLABS_ENABLE_NEW_DIRECTORY_STRUCTURE') || !$event->getIO()->askConfirmation('Would you like to use Symfony 3 directory structure? [y/N] ', false)) {
            return;
        }

        $rootDir = getcwd();
        $appDir = $options['symfony-app-dir'];
        $webDir = $options['symfony-web-dir'];
        $binDir = self::$options['symfony-bin-dir'] = 'bin';
        $varDir = self::$options['symfony-var-dir'] = 'var';

        static::updateDirectoryStructure($event, $rootDir, $appDir, $binDir, $varDir, $webDir);
    }

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
        $bootstrapDir = $autoloadDir = $options['symfony-app-dir'];

        if (self::useNewDirectoryStructure($options)) {
            $bootstrapDir = $options['symfony-var-dir'];
            if (!self::hasDirectory($event, 'symfony-var-dir', $bootstrapDir, 'build bootstrap file')) {
                return;
            }
        }
        if (!self::hasDirectory($event, 'symfony-app-dir', $autoloadDir, 'build bootstrap file')) {
            return;
        }

        static::executeBuildBootstrap($event, $bootstrapDir, $autoloadDir, $options['process-timeout']);
    }

    protected static function hasDirectory(CommandEvent $event, $configName, $path, $actionName)
    {
        if (!is_dir($path)) {
            $event->getIO()->write(sprintf('The %s (%s) specified in composer.json was not found in %s, can not %s.', $configName, $path, getcwd(), $actionName));

            return false;
        }

        return true;
    }

    /**
     * Clears the Symfony cache.
     *
     * @param $event CommandEvent A instance
     */
    public static function clearCache(CommandEvent $event)
    {
        $options = self::getOptions($event);
        $consoleDir = self::getConsoleDir($event, 'clear the cache');

        if (null === $consoleDir) {
            return;
        }

        $warmup = '';
        if (!$options['symfony-cache-warmup']) {
            $warmup = ' --no-warmup';
        }

        static::executeCommand($event, $consoleDir, 'cache:clear'.$warmup, $options['process-timeout']);
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
        $consoleDir = self::getConsoleDir($event, 'install assets');

        if (null === $consoleDir) {
            return;
        }

        $webDir = $options['symfony-web-dir'];

        $symlink = '';
        if ($options['symfony-assets-install'] == 'symlink') {
            $symlink = '--symlink ';
        } elseif ($options['symfony-assets-install'] == 'relative') {
            $symlink = '--symlink --relative ';
        }

        if (!self::hasDirectory($event, 'symfony-web-dir', $webDir, 'install assets')) {
            return;
        }

        static::executeCommand($event, $consoleDir, 'assets:install '.$symlink.escapeshellarg($webDir));
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
        $fs = new Filesystem();

        $newDirectoryStructure = self::useNewDirectoryStructure($options);

        if (!$newDirectoryStructure) {
            if (!self::hasDirectory($event, 'symfony-app-dir', $appDir, 'install the requirements files')) {
                return;
            }
            $fs->copy(__DIR__.'/../Resources/skeleton/app/SymfonyRequirements.php', $appDir.'/SymfonyRequirements.php', true);
            $fs->copy(__DIR__.'/../Resources/skeleton/app/check.php', $appDir.'/check.php', true);
        } else {
            $binDir = $options['symfony-bin-dir'];
            $varDir = $options['symfony-var-dir'];
            if (!self::hasDirectory($event, 'symfony-var-dir', $varDir, 'install the requirements files')) {
                return;
            }
            if (!self::hasDirectory($event, 'symfony-bin-dir', $binDir, 'install the requirements files')) {
                return;
            }
            $fs->copy(__DIR__.'/../Resources/skeleton/app/SymfonyRequirements.php', $varDir.'/SymfonyRequirements.php', true);
            $fs->copy(__DIR__.'/../Resources/skeleton/app/check.php', $binDir.'/symfony_requirements', true);
            $fs->remove(array($appDir.'/check.php', $appDir.'/SymfonyRequirements.php', true));

            $fs->dumpFile($binDir.'/symfony_requirements', '#!/usr/bin/env php'.PHP_EOL.str_replace(".'/SymfonyRequirements.php'", ".'/".$fs->makePathRelative($varDir, $binDir)."SymfonyRequirements.php'", file_get_contents($binDir.'/symfony_requirements')), 0755);
        }

        $webDir = $options['symfony-web-dir'];

        // if the user has already removed the config.php file, do nothing
        // as the file must be removed for production use
        if ($fs->exists($webDir.'/config.php')) {
            if (!$newDirectoryStructure) {
                $fs->copy(__DIR__ . '/../Resources/skeleton/web/config.php', $webDir . '/config.php', true);
            } else {
                $fs->dumpFile($webDir.'/config.php', str_replace('/../app/SymfonyRequirements.php', '/'.$fs->makePathRelative($varDir, $webDir).'SymfonyRequirements.php', file_get_contents(__DIR__ . '/../Resources/skeleton/web/config.php')));
            }
        }
    }

    public static function removeSymfonyStandardFiles(CommandEvent $event)
    {
        $options = self::getOptions($event);
        $appDir = $options['symfony-app-dir'];

        if (!is_dir($appDir)) {
            return;
        }

        if (!is_dir($appDir.'/SymfonyStandard')) {
            return;
        }

        $fs = new Filesystem();
        $fs->remove($appDir.'/SymfonyStandard');
    }

    public static function installAcmeDemoBundle(CommandEvent $event)
    {
        $rootDir = getcwd();
        $options = self::getOptions($event);

        if (file_exists($rootDir.'/src/Acme/DemoBundle')) {
            return;
        }

        if (!getenv('SENSIOLABS_FORCE_ACME_DEMO')) {
            if (!$event->getIO()->askConfirmation('Would you like to install Acme demo bundle? [y/N] ', false)) {
                return;
            }
        }

        $event->getIO()->write('Installing the Acme demo bundle.');

        $appDir = $options['symfony-app-dir'];

        $kernelFile = $appDir.'/AppKernel.php';

        $fs = new Filesystem();
        $fs->mirror(__DIR__.'/../Resources/skeleton/acme-demo-bundle', $rootDir.'/src', null, array('override'));

        $ref = '$bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();';
        $bundleDeclaration = "\$bundles[] = new Acme\\DemoBundle\\AcmeDemoBundle();";
        $content = file_get_contents($kernelFile);

        if (false === strpos($content, $bundleDeclaration)) {
            $updatedContent = str_replace($ref, $bundleDeclaration."\n            ".$ref, $content);
            if ($content === $updatedContent) {
                throw new \RuntimeException('Unable to patch %s.', $kernelFile);
            }
            $fs->dumpFile($kernelFile, $updatedContent);
        }

        self::patchAcmeDemoBundleConfiguration($appDir, $fs);
    }

    private static function patchAcmeDemoBundleConfiguration($appDir, Filesystem $fs)
    {
        $routingFile = $appDir.'/config/routing_dev.yml';
        $securityFile = $appDir.'/config/security.yml';

        $routingData = file_get_contents($routingFile).<<<EOF

# AcmeDemoBundle routes (to be removed)
_acme_demo:
    resource: "@AcmeDemoBundle/Resources/config/routing.yml"
EOF;
        $fs->dumpFile($routingFile, $routingData);

        $securityData = <<<EOF
security:
    encoders:
        Symfony\Component\Security\Core\User\User: plaintext

    role_hierarchy:
        ROLE_ADMIN:       ROLE_USER
        ROLE_SUPER_ADMIN: [ROLE_USER, ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]

    providers:
        in_memory:
            memory:
                users:
                    user:  { password: userpass, roles: [ 'ROLE_USER' ] }
                    admin: { password: adminpass, roles: [ 'ROLE_ADMIN' ] }

    firewalls:
        dev:
            pattern:  ^/(_(profiler|wdt)|css|images|js)/
            security: false

        demo_login:
            pattern:  ^/demo/secured/login$
            security: false

        demo_secured_area:
            pattern:    ^/demo/secured/
            form_login:
                check_path: _demo_security_check
                login_path: _demo_login
            logout:
                path:   _demo_logout
                target: _demo
            #anonymous: ~
            #http_basic:
            #    realm: "Secured Demo Area"

    access_control:
        #- { path: ^/login, roles: IS_AUTHENTICATED_ANONYMOUSLY, requires_channel: https }
EOF;

        $fs->dumpFile($securityFile, $securityData);
    }

    public static function doBuildBootstrap($bootstrapDir, $autoloadDir = null, $useNewDirectoryStructure = false)
    {
        $file = $bootstrapDir.'/bootstrap.php.cache';
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
        if (is_array($autoloaders[0]) && method_exists($autoloaders[0][0], 'findFile') && $autoloaders[0][0]->findFile('Symfony\\Bundle\\FrameworkBundle\\HttpKernel')) {
            $classes[] = 'Symfony\\Bundle\\FrameworkBundle\\HttpKernel';
        } else {
            $classes[] = 'Symfony\\Component\\HttpKernel\\DependencyInjection\\ContainerAwareHttpKernel';
        }

        ClassCollectionLoader::load($classes, dirname($file), basename($file, '.php.cache'), false, false, '.php.cache');

        $fs = new Filesystem();
        $bootstrapContent = substr(file_get_contents($file), 5);

        if ($useNewDirectoryStructure) {
            $cacheDir = $fs->makePathRelative($bootstrapDir, $autoloadDir);
            $bootstrapContent = str_replace(array("return \$this->rootDir.'/logs", "return \$this->rootDir.'/cache"), array("return \$this->rootDir.'/".$cacheDir."logs", "return \$this->rootDir.'/".$cacheDir."cache"), $bootstrapContent);
        }

        if ($autoloadDir) {
            $fs = new Filesystem();
            $autoloadDir = $fs->makePathRelative($autoloadDir, $bootstrapDir);
        }

        file_put_contents($file, sprintf("<?php

namespace { \$loader = require_once __DIR__.'/".$autoloadDir."autoload.php'; }

%s

namespace { return \$loader; }
            ", $bootstrapContent));
    }

    protected static function executeCommand(CommandEvent $event, $consoleDir, $cmd, $timeout = 300)
    {
        $php = escapeshellarg(self::getPhp());
        $console = escapeshellarg($consoleDir.'/console');
        if ($event->getIO()->isDecorated()) {
            $console .= ' --ansi';
        }

        $process = new Process($php.' '.$console.' '.$cmd, null, null, null, $timeout);
        $process->run(function ($type, $buffer) use ($event) { $event->getIO()->write($buffer, false); });
        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('An error occurred when executing the "%s" command.', escapeshellarg($cmd)));
        }
    }

    protected static function executeBuildBootstrap(CommandEvent $event, $bootstrapDir, $autoloadDir, $timeout = 300)
    {
        $php = escapeshellarg(self::getPhp());
        $cmd = escapeshellarg(__DIR__.'/../Resources/bin/build_bootstrap.php');
        $bootstrapDir = escapeshellarg($bootstrapDir);
        $autoloadDir = escapeshellarg($autoloadDir);
        $useNewDirectoryStructure = '';
        if (self::useNewDirectoryStructure(self::getOptions($event))) {
            $useNewDirectoryStructure = escapeshellarg('--use-new-directory-structure');
        }

        $process = new Process($php.' '.$cmd.' '.$bootstrapDir.' '.$autoloadDir.' '.$useNewDirectoryStructure, getcwd(), null, null, $timeout);
        $process->run(function ($type, $buffer) use ($event) { $event->getIO()->write($buffer, false); });
        if (!$process->isSuccessful()) {
            throw new \RuntimeException('An error occurred when generating the bootstrap file.');
        }
    }

    protected static function updateDirectoryStructure(CommandEvent $event, $rootDir, $appDir, $binDir, $varDir, $webDir)
    {
        $event->getIO()->write('Updating Symfony directory structure...');

        $fs = new Filesystem();

        $fs->mkdir(array($binDir, $varDir));

        foreach (array(
            $appDir.'/console' => $binDir.'/console',
            $appDir.'/phpunit.xml.dist' => $rootDir.'/phpunit.xml.dist',
        ) as $source => $target) {
            $fs->rename($source, $target, true);
        }

        foreach (array('/logs', '/cache') as $dir) {
            $fs->rename($appDir.$dir, $varDir.$dir);
        }

        $gitignore = <<<EOF
/web/bundles/
/app/config/parameters.yml
/var/bootstrap.php.cache
/var/SymfonyRequirements.php
/var/cache/*
/var/logs/*
!var/cache/.gitkeep
!var/logs/.gitkeep
/build/
/vendor/
/bin/*
!bin/console
!bin/symfony_requirements
/composer.phar
EOF;
        $phpunitKernelBefore = <<<EOF
    <!--
    <php>
        <server name="KERNEL_DIR" value="/path/to/your/app/" />
    </php>
    -->
EOF;
        $phpunitKernelAfter = <<<EOF
    <php>
        <server name="KERNEL_DIR" value="$appDir/" />
    </php>
EOF;
        $phpunit = str_replace(array('<directory>../src/', '"bootstrap.php.cache"', $phpunitKernelBefore), array('<directory>src/', '"'.$varDir.'/bootstrap.php.cache"', $phpunitKernelAfter),  file_get_contents($rootDir.'/phpunit.xml.dist'));
        $composer = str_replace("\"symfony-app-dir\": \"app\",", "\"symfony-app-dir\": \"app\",\n        \"symfony-bin-dir\": \"bin\",\n        \"symfony-var-dir\": \"var\",", file_get_contents($rootDir.'/composer.json'));
        $travis = str_replace("\nscript: phpunit -c app", '', file_get_contents($rootDir.'/.travis.yml'));

        $fs->dumpFile($webDir.'/app.php', str_replace($appDir.'/bootstrap.php.cache', $varDir.'/bootstrap.php.cache', file_get_contents($webDir.'/app.php')));
        $fs->dumpFile($webDir.'/app_dev.php', str_replace($appDir.'/bootstrap.php.cache', $varDir.'/bootstrap.php.cache', file_get_contents($webDir.'/app_dev.php')));
        $fs->dumpFile($binDir.'/console', str_replace(array(".'/bootstrap.php.cache'", ".'/AppKernel.php'"), array(".'/".$fs->makePathRelative($varDir, $binDir)."bootstrap.php.cache'", ".'/".$fs->makePathRelative($appDir, $binDir)."AppKernel.php'"), file_get_contents($binDir.'/console')));
        $fs->dumpFile($rootDir.'/phpunit.xml.dist', $phpunit);
        $fs->dumpFile($rootDir.'/composer.json', $composer);
        $fs->dumpFile($rootDir.'/.travis.yml', $travis);

        $fs->dumpFile($rootDir.'/.gitignore', $gitignore);

        $fs->chmod($binDir.'/console', 0755);
    }

    protected static function getOptions(CommandEvent $event)
    {
        $options = array_merge(self::$options, $event->getComposer()->getPackage()->getExtra());

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

    /**
     * Returns a relative path to the directory that contains the `console` command.
     *
     * @param CommandEvent $event      The command event.
     * @param string       $actionName The name of the action
     *
     * @return string|null The path to the console directory, null if not found.
     */
    protected static function getConsoleDir(CommandEvent $event, $actionName)
    {
        $options = self::getOptions($event);

        if (self::useNewDirectoryStructure($options)) {
            if (!self::hasDirectory($event, 'symfony-bin-dir', $options['symfony-bin-dir'], $actionName)) {
                return;
            }

            return $options['symfony-bin-dir'];
        }

        if (!self::hasDirectory($event, 'symfony-app-dir', $options['symfony-app-dir'], 'execute command')) {
            return;
        }

        return $options['symfony-app-dir'];
    }

    /**
     * Returns true if the new directory structure is used.
     *
     * @param array $options Composer options
     *
     * @return bool
     */
    protected static function useNewDirectoryStructure(array $options)
    {
        return isset($options['symfony-var-dir']) && is_dir($options['symfony-var-dir']);
    }
}
