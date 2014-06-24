<?php

namespace FOS\MessageBundle\Validator;

use Symfony\Component\Validator\Constraint;

class Spam extends Constraint
{
    public $message = 'Sorry, your message looks like spam';

    public function validatedBy()
    {
        return 'fos_message.validator.spam';
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
