<?php

namespace CpChart\Exception;

use Exception;

/**
 * @author Piotr Szymaszek
 */
class IncorrectBarcodeNumberException extends FactoryException
{
    protected $message = 'The barcode class for the number %s does not exist!';

    /**
     * @param int $number - the requested barcode class number
     * @param int $code
     * @param Exception $previous
     */
    public function __construct($number, $code = null, Exception $previous = null)
    {
        parent::__construct(sprintf($this->message, $number), $code, $previous);
    }
}
