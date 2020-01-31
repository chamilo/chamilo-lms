<?php

namespace PhpCoveralls\Bundle\CoverallsBundle\Config;

use PhpCoveralls\Component\File\Path;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Parser;

/**
 * Coveralls API configurator.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class Configurator
{
    // API

    /**
     * Load configuration.
     *
     * @param string         $coverallsYmlPath Path to .coveralls.yml.
     * @param string         $rootDir          path to project root directory
     * @param InputInterface $input|null       Input arguments
     *
     * @throws \Symfony\Component\Yaml\Exception\ParseException If the YAML is not valid
     *
     * @return \PhpCoveralls\Bundle\CoverallsBundle\Config\Configuration
     */
    public function load($coverallsYmlPath, $rootDir, InputInterface $input = null)
    {
        $yml = $this->parse($coverallsYmlPath);
        $options = $this->process($yml);

        return $this->createConfiguration($options, $rootDir, $input);
    }

    // Internal method

    /**
     * Parse .coveralls.yml.
     *
     * @param string $coverallsYmlPath Path to .coveralls.yml.
     *
     * @throws \Symfony\Component\Yaml\Exception\ParseException If the YAML is not valid
     *
     * @return array
     */
    protected function parse($coverallsYmlPath)
    {
        $file = new Path();
        $path = realpath($coverallsYmlPath);

        if ($file->isRealFileReadable($path)) {
            $parser = new Parser();
            $yml = $parser->parse(file_get_contents($path));

            return empty($yml) ? [] : $yml;
        }

        return [];
    }

    /**
     * Process parsed configuration according to the configuration definition.
     *
     * @param array $yml parsed configuration
     *
     * @return array
     */
    protected function process(array $yml)
    {
        $processor = new Processor();
        $configuration = new CoverallsConfiguration();

        return $processor->processConfiguration($configuration, ['coveralls' => $yml]);
    }

    /**
     * Create coveralls configuration.
     *
     * @param array          $options    processed configuration
     * @param string         $rootDir    path to project root directory
     * @param InputInterface $input|null Input arguments
     *
     * @return \PhpCoveralls\Bundle\CoverallsBundle\Config\Configuration
     */
    protected function createConfiguration(array $options, $rootDir, InputInterface $input = null)
    {
        $configuration = new Configuration();
        $file = new Path();

        $repoToken = $options['repo_token'];
        $repoSecretToken = $options['repo_secret_token'];

        // handle coverage clover
        $coverage_clover = $options['coverage_clover'];
        if ($input !== null && $input->hasOption('coverage_clover')) {
            $option = $input->getOption('coverage_clover');
            if (!empty($option)) {
                $coverage_clover = $option;
            }
        }

        // handle output json path
        $json_path = $options['json_path'];
        if ($input !== null && $input->hasOption('json_path')) {
            $option = $input->getOption('json_path');
            if (!empty($option)) {
                $json_path = $option;
            }
        }

        return $configuration
            ->setEntrypoint($options['entry_point'])
            ->setRepoToken($repoToken !== null ? $repoToken : $repoSecretToken)
            ->setServiceName($options['service_name'])
            ->setRootDir($rootDir)
            ->setCloverXmlPaths($this->ensureCloverXmlPaths($coverage_clover, $rootDir, $file))
            ->setJsonPath($this->ensureJsonPath($json_path, $rootDir, $file))
            ->setExcludeNoStatements($options['exclude_no_stmt']);
    }

    /**
     * Ensure coverage_clover is valid.
     *
     * @param string $option  coverage_clover option
     * @param string $rootDir path to project root directory
     * @param Path   $file    path object
     *
     * @throws \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     *
     * @return string[] valid Absolute paths of coverage_clover
     */
    protected function ensureCloverXmlPaths($option, $rootDir, Path $file)
    {
        if (is_array($option)) {
            return $this->getGlobPathsFromArrayOption($option, $rootDir, $file);
        }

        return $this->getGlobPathsFromStringOption($option, $rootDir, $file);
    }

    /**
     * Return absolute paths from glob path.
     *
     * @param string $path absolute path
     *
     * @throws \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     *
     * @return string[] absolute paths
     */
    protected function getGlobPaths($path)
    {
        $paths = [];
        $iterator = new \GlobIterator($path);

        foreach ($iterator as $fileInfo) {
            /* @var $fileInfo \SplFileInfo */
            $paths[] = $fileInfo->getPathname();
        }

        // validate
        if (count($paths) === 0) {
            throw new InvalidConfigurationException("coverage_clover XML file is not readable: ${path}");
        }

        return $paths;
    }

    /**
     * Return absolute paths from string option value.
     *
     * @param string $option  coverage_clover option value
     * @param string $rootDir path to project root directory
     * @param Path   $file    path object
     *
     * @throws \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     *
     * @return string[] absolute paths
     */
    protected function getGlobPathsFromStringOption($option, $rootDir, Path $file)
    {
        if (!is_string($option)) {
            throw new InvalidConfigurationException('coverage_clover XML file option must be a string');
        }

        // normalize
        $path = $file->toAbsolutePath($option, $rootDir);

        return $this->getGlobPaths($path);
    }

    /**
     * Return absolute paths from array option values.
     *
     * @param array  $options coverage_clover option values
     * @param string $rootDir path to project root directory
     * @param Path   $file    path object
     *
     * @throws \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     *
     * @return string[] absolute paths
     */
    protected function getGlobPathsFromArrayOption(array $options, $rootDir, Path $file)
    {
        $paths = [];

        foreach ($options as $option) {
            $paths = array_merge($paths, $this->getGlobPathsFromStringOption($option, $rootDir, $file));
        }

        return $paths;
    }

    /**
     * Ensure json_path is valid.
     *
     * @param string $option  json_path option
     * @param string $rootDir path to project root directory
     * @param Path   $file    path object
     *
     * @throws \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     *
     * @return string valid json_path
     */
    protected function ensureJsonPath($option, $rootDir, Path $file)
    {
        // normalize
        $realpath = $file->getRealWritingFilePath($option, $rootDir);

        // validate file
        $realFilePath = $file->getRealPath($realpath, $rootDir);

        if ($realFilePath !== false && !$file->isRealFileWritable($realFilePath)) {
            throw new InvalidConfigurationException("json_path is not writable: ${realFilePath}");
        }

        // validate parent dir
        $realDir = $file->getRealDir($realpath, $rootDir);

        if (!$file->isRealDirWritable($realDir)) {
            throw new InvalidConfigurationException("json_path is not writable: ${realFilePath}");
        }

        return $realpath;
    }
}
