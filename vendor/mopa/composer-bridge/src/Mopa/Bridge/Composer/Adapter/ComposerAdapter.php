<?php
namespace Mopa\Bridge\Composer\Adapter;

use Composer;
use Composer\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\ClassLoader\UniversalClassLoader;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * ComposerAdapter to support Composer in symfony2 console apps
 * If Composer is not installed via vendors it checks for a composer.phar in $pathToComposer and environment
 */
class ComposerAdapter
{
    protected static $composer;
    protected static $application;
    /**
     * Find a composer.phar in given path or in environment
     *
     * @param unknown_type $pathToComposer
     */
    public static function whichComposer($pathToComposer)
    {
        if (file_exists($pathToComposer)) {
            return $pathToComposer;
        }
        if (file_exists('composer.phar')) {
            return 'composer.phar';
        }
        if (file_exists('../composer.phar')) {
            return '../composer.phar';
        }
        $composerExecs = array('composer.phar', 'composer');

        $isUnix = DIRECTORY_SEPARATOR == '/' ? true : false;

        foreach ($composerExecs as $composerExec) {

            $pathToComposer = exec(sprintf($isUnix ? "which %s" : "for %%i in (%s) do @echo.%%~\$PATH:i", $composerExec));

            if (file_exists($pathToComposer)) {
                return $pathToComposer;
            }
        }

        return false;
    }
    /**
     * Create a composer Instance
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected static function createComposer(InputInterface $input, OutputInterface $output, $localConfig = null)
    {
        $HelperSet = new HelperSet();

        return Composer\Factory::create(
                new Composer\IO\ConsoleIO($input, $output, $HelperSet),
                $localConfig
        );
    }
    /**
     * Check for composer in Namespace
     * and include via phar if possible
     */
    public static function checkComposer($pathToComposer = null)
    {
        if (!class_exists("Composer\Factory")) {
            if (false === $pathToComposer = self::whichComposer($pathToComposer)) {
                throw new \RuntimeException("Could not find composer.phar");
            }
            \Phar::loadPhar($pathToComposer, 'composer.phar');
            $loader = new UniversalClassLoader();
            $namespaces = include("phar://composer.phar/vendor/composer/autoload_namespaces.php");
            $loader->registerNamespaces(array_merge(
                array(
                    'Composer' => "phar://composer.phar/src/"
                ),
                $namespaces
            ));
            $loader->register(true);
        }
    }
    /**
     * Returns a instance of composer
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param unknown_type    $pathToComposer
     */
    public static function getComposer(InputInterface $input = null, OutputInterface $output = null, $pathToComposer = null, $required = true, $localConfig = null)
    {
        if ($input == null) {
            $input = new ArrayInput(array());
        }
        if ($output == null) {
            $output = new NullOutput();
        }
        if (null === self::$composer) {
            self::checkComposer($pathToComposer);
            $output->write("Initializing composer ... ");
            try {
                self::$composer = self::createComposer($input, $output, $localConfig);
            } catch (\InvalidArgumentException $e) {
                if ($required) {
                    throw $e;
                }
            }
            $output->writeln("<info>done</info>.");
        }

        return self::$composer;
    }
}
