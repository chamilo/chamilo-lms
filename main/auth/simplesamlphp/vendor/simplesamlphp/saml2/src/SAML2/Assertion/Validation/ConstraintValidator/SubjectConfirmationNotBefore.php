<?php

namespace SAML2\Assertion\Validation\ConstraintValidator;

use SAML2\Assertion\Validation\Result;
use SAML2\Assertion\Validation\SubjectConfirmationConstraintValidator;
use SAML2\Utilities\Temporal;
use SAML2\XML\saml\SubjectConfirmation;

class SubjectConfirmationNotBefore implements
    SubjectConfirmationConstraintValidator
{
    /**
     * @param SubjectConfirmation $subjectConfirmation
     * @param Result $result
     * @return void
     */
    public function validate(
        SubjectConfirmation $subjectConfirmation,
        Result $result
    ) {
        $notBefore = $subjectConfirmation->getSubjectConfirmationData()->getNotBefore();
        if ($notBefore && $notBefore > Temporal::getTime() + 60) {
            $result->addError('NotBefore in SubjectConfirmationData is in the future');
        }
    }
}
