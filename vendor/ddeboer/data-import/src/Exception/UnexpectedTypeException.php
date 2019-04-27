<?php

namespace Ddeboer\DataImport\Exception;

/**
 * @author David de Boer <david@ddeboer.nl>
 */
class UnexpectedTypeException extends UnexpectedValueException
{
    /**
     * @param mixed  $value
     * @param string $expectedType
     */
    public function __construct($value, $expectedType)
    {
        parent::__construct(sprintf(
            'Expected argument of type "%s", "%s" given',
            $expectedType,
            is_object($value) ? get_class($value) : gettype($value)
        ));
    }
}
