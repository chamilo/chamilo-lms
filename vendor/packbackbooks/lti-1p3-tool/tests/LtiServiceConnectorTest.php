<?php namespace Tests;

use PHPUnit\Framework\TestCase;
use Mockery;

use Packback\Lti1p3\Interfaces\LtiRegistrationInterface;
use Packback\Lti1p3\LtiServiceConnector;

class LtiServiceConnectorTest extends TestCase
{

    public function testItInstantiates()
    {
        $registration = Mockery::mock(LtiRegistrationInterface::class);

        $connector = new LtiServiceConnector($registration);

        $this->assertInstanceOf(LtiServiceConnector::class, $connector);
    }

    /**
     * @todo Finish testing
     */
}
