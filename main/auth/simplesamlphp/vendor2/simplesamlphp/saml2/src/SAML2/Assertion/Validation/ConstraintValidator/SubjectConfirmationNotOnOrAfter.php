<?php

namespace SAML2\Assertion\Validation\ConstraintValidator;

use SAML2\Assertion\Validation\Result;
use SAML2\Assertion\Validation\SubjectConfirmationConstraintValidator;
use SAML2\Utilities\Temporal;
use SAML2\XML\saml\SubjectConfirmation;

class SubjectConfirmationNotOnOrAfter implements
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
        $notOnOrAfter = $subjectConfirmation->getSubjectConfirmationData()->getNotOnOrAfter();
        if ($notOnOrAfter && $notOnOrAfter <= Temporal::getTime() - 60) {
            $result->addError('NotOnOrAfter in SubjectConfirmationData is in the past');
        }
    }
}
