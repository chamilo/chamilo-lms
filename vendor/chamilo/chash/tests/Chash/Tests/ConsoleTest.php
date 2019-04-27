<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Chash\Command\Files\CleanConfigFilesCommand;

class ConsoleTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $configurationFileContent = file_get_contents(__DIR__.'/../Resources/configuration.php');
    }

    public function testListCommand()
    {
        $application = new Application();

        $helpers = array(
            'configuration' => new Chash\Helpers\ConfigurationHelper()
        );

        $helperSet = $application->getHelperSet();
        foreach ($helpers as $name => $helper) {
            $helperSet->set($helper, $name);
        }

        //$application->add(new CleanConfigFilesCommand());

        //$command = $application->find('files:clean_config_files');
        //$this->assertEquals('Chash\Command\Files\CleanConfigFilesCommand', get_class($command));

/*        $commandTester = new CommandTester($command);
        $returnCode = $commandTester->execute(
            array(
                'command' => $command->getName(),
                '--conf' => 'chamilo/config/configuration.php'
            )
        );*/



//        var_dump(realpath(__DIR__.'/../../Resources/configuration.php'));

  //      $this->assertRegExp('/11/', $commandTester->getDisplay());

    }
}
