<?php namespace Tests;

use PHPUnit\Framework\TestCase;

use Packback\Lti1p3\Redirect;

class RedirectTest extends TestCase
{

    public function testItInstantiates()
    {
        $redirect = new Redirect('test');

        $this->assertInstanceOf(Redirect::class, $redirect);
    }

    public function testItGetsRedirectUrl()
    {
        $expected = 'expected';
        $redirect = new Redirect($expected);

        $result = $redirect->getRedirectUrl();

        $this->assertEquals($expected, $result);
    }

    /**
     * @todo Finish testing
     */
}
