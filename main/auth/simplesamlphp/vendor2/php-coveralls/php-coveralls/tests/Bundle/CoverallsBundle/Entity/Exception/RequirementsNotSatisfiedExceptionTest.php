<?php

namespace PhpCoveralls\Tests\Bundle\CoverallsBundle\Entity\Exception;

use PhpCoveralls\Bundle\CoverallsBundle\Entity\Exception\RequirementsNotSatisfiedException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpCoveralls\Bundle\CoverallsBundle\Entity\Exception\RequirementsNotSatisfiedException
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class RequirementsNotSatisfiedExceptionTest extends TestCase
{
    // getReadEnv()

    /**
     * @test
     */
    public function shouldNotHaveReadEnvOnConstruction()
    {
        $object = new RequirementsNotSatisfiedException();

        $this->assertNull($object->getReadEnv());
    }

    // setReadEnv()

    /**
     * @test
     */
    public function shouldSetReadEnv()
    {
        $expected = [
            'ENV_NAME' => 'value',
        ];

        $object = new RequirementsNotSatisfiedException();
        $object->setReadEnv($expected);

        $this->assertSame($expected, $object->getReadEnv());
    }

    // getHelpMessage()

    /**
     * @test
     */
    public function shouldGetHelpMessageWithStringEnvVar()
    {
        $expected = [
            'ENV_NAME' => 'value',
        ];

        $object = new RequirementsNotSatisfiedException();
        $object->setReadEnv($expected);

        $message = $object->getHelpMessage();

        $this->assertContains("  - ENV_NAME='value'", $message);
    }

    // getHelpMessage()

    /**
     * @test
     */
    public function shouldGetHelpMessageWithSecretStringEnvVarHidden()
    {
        // Make sure the secret repo token is HIDDEN.
        $env = [
            'COVERALLS_REPO_TOKEN' => 'secret',
        ];
        $expected = "  - COVERALLS_REPO_TOKEN='********(HIDDEN)'";

        $object = new RequirementsNotSatisfiedException();
        $object->setReadEnv($env);

        $message = $object->getHelpMessage();

        $this->assertContains($expected, $message);
    }

    /**
     * @test
     */
    public function shouldGetHelpMessageWithSecretEmptyStringEnvVarShown()
    {
        // Make sure the secret repo token is shown when it's empty.
        $env = [
            'COVERALLS_REPO_TOKEN' => '',
        ];
        $expected = "  - COVERALLS_REPO_TOKEN=''";

        $object = new RequirementsNotSatisfiedException();
        $object->setReadEnv($env);

        $message = $object->getHelpMessage();

        $this->assertContains($expected, $message);
    }

    /**
     * @test
     */
    public function shouldGetHelpMessageWithIntegerEnvVar()
    {
        $expected = [
            'ENV_NAME' => 123,
        ];

        $object = new RequirementsNotSatisfiedException();
        $object->setReadEnv($expected);

        $message = $object->getHelpMessage();

        $this->assertContains('  - ENV_NAME=123', $message);
    }

    /**
     * @test
     */
    public function shouldGetHelpMessageWithBooleanEnvVar()
    {
        $expected = [
            'ENV_NAME' => true,
        ];

        $object = new RequirementsNotSatisfiedException();
        $object->setReadEnv($expected);

        $message = $object->getHelpMessage();

        $this->assertContains('  - ENV_NAME=true', $message);
    }
}
