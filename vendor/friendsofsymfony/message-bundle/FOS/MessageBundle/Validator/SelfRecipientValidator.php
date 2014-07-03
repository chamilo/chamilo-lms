<?php

namespace FOS\MessageBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use FOS\MessageBundle\Security\ParticipantProviderInterface;

class SelfRecipientValidator extends ConstraintValidator
{
    /**
     * @var ParticipantProviderInterface
     */
    protected $participantProvider;

    /**
     * Constructor
     *
     * @param ParticipantProviderInterface $participantProvider
     */
    public function __construct(ParticipantProviderInterface $participantProvider)
    {
        $this->participantProvider = $participantProvider;
    }

    /**
     * Indicates whether the constraint is valid
     *
     * @param object     $recipient
     * @param Constraint $constraint
     */
    public function validate($recipient, Constraint $constraint)
    {
        if ($recipient === $this->participantProvider->getAuthenticatedParticipant()) {
            $this->context->addViolation($constraint->message);
        }
    }
}
