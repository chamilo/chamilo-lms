<?php

/**
 * This file is part of BraincraftedBootstrapBundle.
 *
 * (c) 2012-2013 by Florian Eckerstorfer
 */

namespace Braincrafted\Bundle\BootstrapBundle\Tests\Command;

use \Mockery as m;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;

use Braincrafted\Bundle\BootstrapBundle\Command\GenerateCommand;

/**
 * GenerateCommandTest
 *
 * @category   Test
 * @package    BraincraftedBootstrapBundle
 * @subpackage Command
 * @author     Florian Eckerstorfer <florian@eckerstorfer.co>
 * @copyright  2012-2013 Florian Eckerstorfer
 * @license    http://opensource.org/licenses/MIT The MIT License
 * @link       http://bootstrap.braincrafted.com BraincraftedBootstrapBundle
 * @group      unit
 */
class GenerateCommandTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->twig = m::mock('\Twig_Environment');

        $this->container = m::mock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->container->shouldReceive('get')->with('twig')->andReturn($this->twig);

        $this->kernel = m::mock('Symfony\Component\HttpKernel\KernelInterface');
        $this->kernel->shouldReceive('getName')->andReturn('app');
        $this->kernel->shouldReceive('getEnvironment')->andReturn('prod');
        $this->kernel->shouldReceive('isDebug')->andReturn(false);
        $this->kernel->shouldReceive('getContainer')->andReturn($this->container);
    }

    public function tearDown()
    {
        if (true === file_exists(sprintf('%s/bootstrap.less', __DIR__))) {
            unlink(sprintf('%s/bootstrap.less', __DIR__));
        }
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\Command\GenerateCommand::execute()
     * @covers Braincrafted\Bundle\BootstrapBundle\Command\GenerateCommand::executeGenerateBootstrap()
     */
    public function testExecute()
    {
        $this->container
            ->shouldReceive('getParameter')
            ->with('braincrafted_bootstrap.customize')
            ->andReturn(array(
                'variables_file'     => __DIR__.'/x/variables.less',
                'bootstrap_output'   => __DIR__.'/bootstrap.less',
                'bootstrap_template' => __DIR__.'/bootstrap.html.twig'
            ));
        $this->container->shouldReceive('getParameter')->with('braincrafted_bootstrap.less_filter')->andReturn('less');
        $this->container->shouldReceive('getParameter')->with('braincrafted_bootstrap.assets_dir')->andReturn(__DIR__);

        $this->twig
            ->shouldReceive('render')
            ->with(__DIR__.'/bootstrap.html.twig', array(
                'variables_file'    => './x/variables.less',
                'assets_dir'        => ''
            ));

        // mock the Kernel or create one depending on your needs
        $application = new Application($this->kernel);
        $application->add(new GenerateCommand());

        $command = $application->find('braincrafted:bootstrap:generate');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertRegExp('/Found custom variables file/', $commandTester->getDisplay());
        $this->assertRegExp('/bootstrap\.less/', $commandTester->getDisplay());
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\Command\GenerateCommand::execute()
     */
    public function testExecuteNoVariablesFile()
    {
        $this->container
            ->shouldReceive('getParameter')
            ->with('braincrafted_bootstrap.customize')
            ->andReturn(array('variables_file' => null));

        // mock the Kernel or create one depending on your needs
        $application = new Application($this->kernel);
        $application->add(new GenerateCommand());

        $command = $application->find('braincrafted:bootstrap:generate');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertRegExp('/Found no custom variables\.less file/', $commandTester->getDisplay());
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\Command\GenerateCommand::execute()
     */
    public function testExecuteNoLessFilter()
    {
        $this->container
            ->shouldReceive('getParameter')
            ->with('braincrafted_bootstrap.customize')
            ->andReturn(array('variables_file' => __DIR__.'/x/variables.less'));
        $this->container->shouldReceive('getParameter')->with('braincrafted_bootstrap.less_filter')->andReturn('none');

        // mock the Kernel or create one depending on your needs
        $application = new Application($this->kernel);
        $application->add(new GenerateCommand());

        $command = $application->find('braincrafted:bootstrap:generate');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertRegExp('/configured with "less" or "lessphp"/', $commandTester->getDisplay());
    }
}
