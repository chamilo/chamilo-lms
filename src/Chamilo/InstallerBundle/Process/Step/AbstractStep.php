<?php

namespace Chamilo\InstallerBundle\Process\Step;

use Chamilo\InstallerBundle\CommandExecutor;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Console\Output\StreamOutput;

use Sylius\Bundle\FlowBundle\Process\Step\ControllerStep;

abstract class AbstractStep extends ControllerStep
{
    /**
     * @var Application
     */
    protected $application;

    /**
     * @var StreamOutput
     */
    protected $output;

    /**
     *
     * @param  string $command
     * @param  array  $params
     * @return mixed
     */
    protected function handleAjaxAction($command, $params = array())
    {
        $exitCode = $this->runCommand($command, $params);

        return $this->getRequest()->isXmlHttpRequest()
            ? new JsonResponse(array('result' => true, 'exitCode' => $exitCode))
            : $this->redirect(
                $this->generateUrl(
                    'sylius_flow_display',
                    array(
                        'scenarioAlias' => 'chamilo_installer',
                        'stepName'      => $this->getName(),
                    )
                )
            );
    }

    /**
     * Execute Symfony2 command
     *
     * @param  string $command Command name (for example, "cache:clear")
     * @param  array  $params  [optional] Additional command parameters, like "--force" etc
     * @return int an executed command exit code
     * @throws \Exception
     * @throws \RuntimeException
     */
    protected function runCommand($command, $params = array())
    {
        $application     = $this->getApplication();
        $output          = $this->getOutput();
        $commandExecutor = new CommandExecutor(
            $application->getKernel()->getEnvironment(),
            $output,
            $application
        );
        $output->writeln('');
        $output->writeln(sprintf('[%s] Launching "%s" command', date('Y-m-d H:i:s'), $command));
        error_log(sprintf('[%s] Launching "%s" command', date('Y-m-d H:i:s'), $command));
        error_log($command);
        error_log(print_r($params, 1));
        $mem  = (int)memory_get_usage() / (1024 * 1024);
        $time = time();

        $result = null;
        try {
            $commandExecutor->runCommand($command, $params);
            $result = $commandExecutor->getLastCommandExitCode();
        } catch (\RuntimeException $ex) {
            $result = $ex;
            error_log($ex->getMessage());
        }

        $output->writeln('');
        $output->writeln(
            sprintf(
                'Command "%s" executed in %u second(s), memory usage: %.2fMb',
                $command,
                time() - $time,
                (int)memory_get_usage() / (1024 * 1024) - $mem
            )
        );
        $output->writeln('');

        // check for any error
        if ($result instanceof \RuntimeException) {
            throw $result;
        }

        return $result;
    }

    /**
     * @return Application
     */
    protected function getApplication()
    {
        if (!$this->application) {
            $this->application = new Application($this->get('kernel'));

            $this->application->setAutoExit(false);
        }

        return $this->application;
    }

    /**
     * @return StreamOutput
     */
    protected function getOutput()
    {
        if (!$this->output) {
            $this->output = new StreamOutput(
                fopen($this->container->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . 'chamilo_install.log', 'a+')
            );
        }

        return $this->output;
    }
}
