<?php

namespace PhpCoveralls\Tests\Bundle\CoverallsBundle\Command;

use PhpCoveralls\Bundle\CoverallsBundle\Command\CoverallsJobsCommand;
use PhpCoveralls\Tests\ProjectTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \PhpCoveralls\Bundle\CoverallsBundle\Command\CoverallsJobsCommand
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class CoverallsJobsCommandTest extends ProjectTestCase
{
    protected function setUp()
    {
        $this->setUpDir(realpath(__DIR__ . '/../../..'));
    }

    protected function tearDown()
    {
        $this->rmFile($this->cloverXmlPath);
        $this->rmFile($this->jsonPath);
        $this->rmDir($this->logsDir);
        $this->rmDir($this->buildDir);
    }

    /**
     * @test
     */
    public function shouldExecuteCoverallsJobsCommand()
    {
        $this->makeProjectDir(null, $this->logsDir);
        $this->dumpCloverXml();

        $command = new CoverallsJobsCommand();
        $command->setRootDir($this->rootDir);

        $app = new Application();
        $app->add($command);

        $command = $app->find('coveralls:v1:jobs');
        $commandTester = new CommandTester($command);

        $_SERVER['TRAVIS'] = true;
        $_SERVER['TRAVIS_JOB_ID'] = 'command_test';

        $actual = $commandTester->execute(
            [
                'command' => $command->getName(),
                '--dry-run' => true,
                '--config' => 'coveralls.yml',
                '--env' => 'test',
            ]
        );

        $this->assertSame(0, $actual);

        // It should succeed too with a correct coverage_clover option.
        $actual = $commandTester->execute(
            [
                'command' => $command->getName(),
                '--dry-run' => true,
                '--config' => 'coveralls.yml',
                '--env' => 'test',
                '--coverage_clover' => 'build/logs/clover.xml',
            ]
        );

        $this->assertSame(0, $actual);
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function shouldExecuteCoverallsJobsCommandWithWrongRootDir()
    {
        $this->makeProjectDir(null, $this->logsDir);
        $this->dumpCloverXml();

        $command = new CoverallsJobsCommand();
        $command->setRootDir($this->logsDir); // Wrong rootDir.

        $app = new Application();
        $app->add($command);

        $command = $app->find('coveralls:v1:jobs');
        $commandTester = new CommandTester($command);

        $_SERVER['TRAVIS'] = true;
        $_SERVER['TRAVIS_JOB_ID'] = 'command_test';

        $actual = $commandTester->execute(
            [
                'command' => $command->getName(),
                '--dry-run' => true,
                '--config' => 'coveralls.yml',
                '--env' => 'test',
            ]
        );

        $this->assertSame(0, $actual);
    }

    /**
     * @test
     */
    public function shouldExecuteCoverallsJobsCommandWithRootDirOverride()
    {
        $this->makeProjectDir(null, $this->logsDir);
        $this->dumpCloverXml();

        $command = new CoverallsJobsCommand();
        $command->setRootDir($this->logsDir); // Wrong rootDir.

        $app = new Application();
        $app->add($command);

        $command = $app->find('coveralls:v1:jobs');
        $commandTester = new CommandTester($command);

        $_SERVER['TRAVIS'] = true;
        $_SERVER['TRAVIS_JOB_ID'] = 'command_test';

        $actual = $commandTester->execute(
            [
                'command' => $command->getName(),
                '--dry-run' => true,
                '--config' => 'coveralls.yml',
                '--env' => 'test',
                // Overriding with a correct one.
                '--root_dir' => $this->rootDir,
            ]
        );

        $this->assertSame(0, $actual);
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function shouldExecuteCoverallsJobsCommandThrowInvalidConfigurationException()
    {
        $this->makeProjectDir(null, $this->logsDir);
        $this->dumpCloverXml();

        $command = new CoverallsJobsCommand();
        $command->setRootDir($this->rootDir);

        $app = new Application();
        $app->add($command);

        $command = $app->find('coveralls:v1:jobs');
        $commandTester = new CommandTester($command);

        $_SERVER['TRAVIS'] = true;
        $_SERVER['TRAVIS_JOB_ID'] = 'command_test';

        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--dry-run' => true,
                '--config' => 'coveralls.yml',
                '--env' => 'test',
                '--coverage_clover' => 'nonexistent.xml',
            ]
        );
    }

    /**
     * @return string
     */
    protected function getCloverXml()
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<coverage generated="1365848893">
  <project timestamp="1365848893">
    <file name="%s/test.php">
      <class name="TestFile" namespace="global">
        <metrics methods="1" coveredmethods="0" conditionals="0" coveredconditionals="0" statements="1" coveredstatements="0" elements="2" coveredelements="0"/>
      </class>
      <line num="5" type="method" name="__construct" crap="1" count="0"/>
      <line num="7" type="stmt" count="0"/>
    </file>
    <package name="Hoge">
      <file name="%s/test2.php">
        <class name="TestFile" namespace="Hoge">
          <metrics methods="1" coveredmethods="0" conditionals="0" coveredconditionals="0" statements="1" coveredstatements="0" elements="2" coveredelements="0"/>
        </class>
        <line num="6" type="method" name="__construct" crap="1" count="0"/>
        <line num="8" type="stmt" count="0"/>
      </file>
    </package>
  </project>
</coverage>
XML;

        return sprintf($xml, $this->srcDir, $this->srcDir);
    }

    protected function dumpCloverXml()
    {
        file_put_contents($this->cloverXmlPath, $this->getCloverXml());
    }
}
