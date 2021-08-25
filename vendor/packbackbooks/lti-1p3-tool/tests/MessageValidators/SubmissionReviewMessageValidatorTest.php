<?php namespace Tests\MessageValidators;

use PHPUnit\Framework\TestCase;

use Packback\Lti1p3\MessageValidators\SubmissionReviewMessageValidator;

class SubmissionReviewMessageValidatorTest extends TestCase
{

    public function testItInstantiates()
    {
        $validator = new SubmissionReviewMessageValidator([]);

        $this->assertInstanceOf(SubmissionReviewMessageValidator::class, $validator);
    }
}
