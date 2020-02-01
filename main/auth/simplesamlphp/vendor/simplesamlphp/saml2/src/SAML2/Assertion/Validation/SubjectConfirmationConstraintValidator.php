<?php

namespace SAML2\Assertion\Validation;

use SAML2\XML\saml\SubjectConfirmation;

interface SubjectConfirmationConstraintValidator
{
    /**
     * @param SubjectConfirmation $subjectConfirmation
     * @param Result $result
     * @return void
     */
    public function validate(
        SubjectConfirmation $subjectConfirmation,
        Result $result
    );
}
