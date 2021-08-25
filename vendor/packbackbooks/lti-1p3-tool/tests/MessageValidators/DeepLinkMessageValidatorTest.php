<?php namespace Tests\MessageValidators;

use PHPUnit\Framework\TestCase;

use Packback\Lti1p3\MessageValidators\DeepLinkMessageValidator;

class DeepLinkMessageValidatorTest extends TestCase
{

    public function testItInstantiates()
    {
        $validator = new DeepLinkMessageValidator([]);

        $this->assertInstanceOf(DeepLinkMessageValidator::class, $validator);
    }
}
