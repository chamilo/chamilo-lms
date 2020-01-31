<?php

namespace SAML2\Response\Validation;

use SAML2\Response;

class Validator
{
    /**
     * @var \SAML2\Response\Validation\ConstraintValidator[]
     */
    protected $constraints;


    /**
     * @param ConstraintValidator $constraint
     * @return void
     */
    public function addConstraintValidator(ConstraintValidator $constraint)
    {
        $this->constraints[] = $constraint;
    }


    /**
     * @param Response $response
     * @return Result
     */
    public function validate(Response $response)
    {
        $result = new Result();
        foreach ($this->constraints as $validator) {
            $validator->validate($response, $result);
        }

        return $result;
    }
}
