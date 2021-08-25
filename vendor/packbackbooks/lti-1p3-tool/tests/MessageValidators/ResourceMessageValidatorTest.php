<?php namespace Tests\MessageValidators;

use PHPUnit\Framework\TestCase;

use Packback\Lti1p3\MessageValidators\ResourceMessageValidator;

class ResourceMessageValidatorTest extends TestCase
{

    public function testItInstantiates()
    {
        $validator = new ResourceMessageValidator([]);

        $this->assertInstanceOf(ResourceMessageValidator::class, $validator);
    }
}
