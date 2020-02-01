<?php

namespace SAML2\Assertion\Transformer;

use SAML2\Assertion;

interface Transformer
{
    /**
     * @param \SAML2\Assertion $assertion
     *
     * @return \SAML2\Assertion
     */
    public function transform(Assertion $assertion);
}
