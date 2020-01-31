<?php

namespace SAML2\Response\Validation;

use SAML2\Response;

interface ConstraintValidator
{
    /**
     * @param Response $response
     * @param Result $result
     * @return void
     */
    public function validate(Response $response, Result $result);
}
