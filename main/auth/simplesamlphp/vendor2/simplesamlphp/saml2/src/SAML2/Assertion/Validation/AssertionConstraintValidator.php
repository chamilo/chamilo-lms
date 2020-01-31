<?php

namespace SAML2\Assertion\Validation;

use SAML2\Assertion;

interface AssertionConstraintValidator
{
    /**
     * @param Assertion $assertion
     * @param Result $result
     * @return void
     */
    public function validate(Assertion $assertion, Result $result);
}
