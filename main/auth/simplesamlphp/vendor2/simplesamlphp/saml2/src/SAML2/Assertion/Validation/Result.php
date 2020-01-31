<?php

namespace SAML2\Assertion\Validation;

use SAML2\Exception\InvalidArgumentException;

/**
 * Simple Result object
 */
class Result
{
    /**
     * @var array
     */
    private $errors = [];


    /**
     * @param $message
     * @return void
     */
    public function addError($message)
    {
        if (!is_string($message)) {
            throw InvalidArgumentException::invalidType('string', $message);
        }

        $this->errors[] = $message;
    }


    /**
     * @return bool
     */
    public function isValid()
    {
        return empty($this->errors);
    }


    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
