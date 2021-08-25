<?php namespace Tests;

use PHPUnit\Framework\TestCase;
use Mockery;

use Packback\Lti1p3\Interfaces\LtiRegistrationInterface;
use Packback\Lti1p3\LtiDeepLink;
use Packback\Lti1p3\Nonce;

class LtiDeepLinkTest extends TestCase
{
    public function testItInstantiates()
    {
        $registration = Mockery::mock(LtiRegistrationInterface::class);

        $deepLink = new LtiDeepLink($registration, 'test', []);

        $this->assertInstanceOf(LtiDeepLink::class, $deepLink);
    }

    /**
     * @todo Figure out how to test this
     */
    // public function testItGetsJwtResponse()
    // {
    //     $registration = Mockery::mock(LtiRegistrationInterface::class);
    //     $registration->shouldReceive('getClientId')
    //         ->once()->andReturn('client_id');
    //     $registration->shouldReceive('getIssuer')
    //         ->once()->andReturn('issuer');
    //     $registration->shouldReceive('getToolPrivateKey')
    //         ->once()->andReturn(file_get_contents(__DIR__.'/data/private.key'));
    //     $registration->shouldReceive('getKid')
    //         ->once()->andReturn('kid');
    //     $deepLink = new LtiDeepLink($registration, 'deployment_id', [
    //         'data' => 'test_data'
    //     ]);
    //     $resource = Mockery::mock();
    //     $resource->shouldReceive('toArray')
    //         ->once()->andReturn(['resource']);

    //     $result = $deepLink->getResponseJwt([ $resource ]);

    //     $expected = '';
    //     $this->assertEquals(
    //         $expected,
    //         $result
    //     );
    // }
}
