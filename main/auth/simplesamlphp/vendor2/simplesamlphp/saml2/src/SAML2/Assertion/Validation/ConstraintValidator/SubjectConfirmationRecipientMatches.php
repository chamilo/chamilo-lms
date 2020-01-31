<?php

namespace SAML2\Assertion\Validation\ConstraintValidator;

use SAML2\Assertion\Validation\Result;
use SAML2\Assertion\Validation\SubjectConfirmationConstraintValidator;
use SAML2\Configuration\Destination;
use SAML2\XML\saml\SubjectConfirmation;

class SubjectConfirmationRecipientMatches implements
    SubjectConfirmationConstraintValidator
{
    /**
     * @var \SAML2\Configuration\Destination
     */
    private $destination;


    /**
     * Constructor for SubjectConfirmationRecipientMatches
     * @param Destination $destination
     */
    public function __construct(Destination $destination)
    {
        $this->destination = $destination;
    }


    /**
     * @param SubjectConfirmation
     * @param Result $result
     * @return void
     */
    public function validate(
        SubjectConfirmation $subjectConfirmation,
        Result $result
    ) {
        $recipient = $subjectConfirmation->getSubjectConfirmationData()->getRecipient();
        if ($recipient && !$this->destination->equals(new Destination($recipient))) {
            $result->addError(sprintf(
                'Recipient in SubjectConfirmationData ("%s") does not match the current destination ("%s")',
                $recipient,
                $this->destination
            ));
        }
    }
}
