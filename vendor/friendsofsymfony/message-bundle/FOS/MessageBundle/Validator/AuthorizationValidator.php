<?php

namespace FOS\MessageBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use FOS\MessageBundle\Security\AuthorizerInterface;

class AuthorizationValidator extends ConstraintValidator
{
    /**
     * @var AuthorizerInterface
     */
    protected $authorizer;

    /**
     * Constructor
     *
     * @param AuthorizerInterface $authorizer
     */
    public function __construct(AuthorizerInterface $authorizer)
    {
        $this->authorizer = $authorizer;
    }

    /**
     * Indicates whether the constraint is valid
     *
     * @param object     $recipient
     * @param Constraint $constraint
     */
    public function validate($recipient, Constraint $constraint)
    {
        if ($recipient && !$this->authorizer->canMessageParticipant($recipient)) {
            $this->context->addViolation($constraint->message);
        }
    }
}
