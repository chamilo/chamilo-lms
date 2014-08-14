<?php

namespace Chamilo\InstallerBundle;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Process\ProcessBuilder;

use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;
use Chamilo\InstallerBundle\Process\PhpExecutableFinder;

class CommandExecutor
{
    /**
     * @var string|null
     */
    protected $env;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var OroDataCacheManager
     */
    protected $dataCacheManager;

    /**
     * @var int
     */
    protected $lastCommandExitCode;

    /**
     * @var int
     */
    protected $defaultTimeout = 300;

    /**
     * Constructor
     *
     * @param string|null         $env
     * @param OutputInterface     $output
     * @param Application         $application
     * @param OroDataCacheManager $dataCacheManager
     */
    public function __construct(
        $env,
        OutputInterface $output,
        Application $application
        //OroDataCacheManager $dataCacheManager = null
    ) {
        $this->env              = $env;
        $this->output           = $output;
        $this->application      = $application;
        //$this->dataCacheManager = $dataCacheManager;
    }

    /**
     * Launches a command.
     * If '--process-isolation' parameter is specified the command will be launched as a separate process.
     * In this case you can parameter '--process-timeout' to set the process timeout
     * in seconds. Default timeout is 300 seconds.
     * If '--ignore-errors' parameter is specified any errors are ignored;
     * otherwise, an exception is raises if an error happened.
     *
     * @param string $command
     * @param array  $params
     * @return CommandExecutor
     * @throws \RuntimeException if command failed and '--ignore-errors' parameter is not specified
     */
    public function runCommand($command, $params = [])
    {
        $params = array_merge(
            [
                'command' => $command
            ],
            $params
        );
        if ($this->env && $this->env !== 'dev') {
            $params['--env'] = $this->env;
        }
        $ignoreErrors = false;
        if (array_key_exists('--ignore-errors', $params)) {
            $ignoreErrors = true;
            unset($params['--ignore-errors']);
        }

        if (array_key_exists('--process-isolation', $params)) {
            unset($params['--process-isolation']);
            $pb = new ProcessBuilder();
            $pb
                ->add($this->getPhp())
                ->add($_SERVER['argv'][0]);

            if (array_key_exists('--process-timeout', $params)) {
                $pb->setTimeout($params['--process-timeout']);
                unset($params['--process-timeout']);
            } else {
                $pb->setTimeout($this->defaultTimeout);
            }

            foreach ($params as $name => $val) {
                $this->processParameter($pb, $name, $val);
            }

            $process = $pb
                ->inheritEnvironmentVariables(true)
                ->getProcess();

            $output = $this->output;

            $process->run(
                function ($type, $data) use ($output) {
                    $output->write($data);
                }
            );
            $this->lastCommandExitCode = $process->getExitCode();

            // synchronize all data caches
            /*if ($this->dataCacheManager) {
                $this->dataCacheManager->sync();
            }*/
        } else {
            $this->application->setAutoExit(false);
            $this->lastCommandExitCode = $this->application->run(new ArrayInput($params), $this->output);
        }

        $this->processResult($ignoreErrors);

        return $this;
    }

    /**
     * Gets an exit code of last executed command
     *
     * @return int
     */
    public function getLastCommandExitCode()
    {
        return $this->lastCommandExitCode;
    }

    /**
     * @param bool $ignoreErrors
     * @throws \RuntimeException
     */
    protected function processResult($ignoreErrors)
    {
        if (0 !== $this->lastCommandExitCode) {
            if ($ignoreErrors) {
                $this->output->writeln(
                    sprintf(
                        '<error>The command terminated with an exit code: %u.</error>',
                        $this->lastCommandExitCode
                    )
                );
            } else {
                throw new \RuntimeException(
                    sprintf('The command terminated with an exit code: %u.', $this->lastCommandExitCode)
                );
            }
        }
    }

    /**
     * @param ProcessBuilder    $pb
     * @param string            $name
     * @param array|string|null $value
     */
    protected function processParameter(ProcessBuilder $pb, $name, $value)
    {
        if ($name && '-' === $name[0]) {
            if ($value === true) {
                $this->addParameter($pb, $name);
            } else {
                $this->addParameter($pb, $name, $value);
            }
        } else {
            $this->addParameter($pb, $value);
        }
    }

    /**
     * @param ProcessBuilder    $pb
     * @param string            $name
     * @param array|string|null $value
     */
    protected function addParameter(ProcessBuilder $pb, $name, $value = null)
    {
        $parameters = array();

        if (null !== $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $parameters[] = sprintf('%s=%s', $name, $item);
                }
            } else {
                $parameters[] = sprintf('%s=%s', $name, $value);
            }
        } else {
            $parameters[] = $name;
        }

        foreach ($parameters as $parameter) {
            $pb->add($parameter);
        }
    }

    /**
     * Finds the PHP executable.
     *
     * @return string
     * @throws FileNotFoundException
     */
    protected function getPhp()
    {
        $phpFinder = new PhpExecutableFinder();
        $phpPath   = $phpFinder->find();
        if (!$phpPath) {
            throw new FileNotFoundException('The PHP executable could not be found.');
        }

        return $phpPath;
    }

    /**
     * @return int
     */
    public function getDefaultTimeout()
    {
        return $this->defaultTimeout;
    }

    /**
     * @param int $defaultTimeout
     */
    public function setDefaultTimeout($defaultTimeout)
    {
        $this->defaultTimeout = $defaultTimeout;
    }
}
