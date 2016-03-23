<?php

namespace Ddeboer\DataImport\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends \Exception implements ExceptionInterface
{
    private $violations;

    private $lineNumber;

    public function __construct(ConstraintViolationListInterface $list, $line)
    {
        $this->violations = $list;
        $this->lineNumber = $line;
    }

    public function getViolations()
    {
        return $this->violations;
    }

    public function getLineNumber()
    {
        return $this->lineNumber;
    }
}
