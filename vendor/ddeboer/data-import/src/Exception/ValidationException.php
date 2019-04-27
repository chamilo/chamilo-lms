<?php

namespace Ddeboer\DataImport\Exception;

use Ddeboer\DataImport\Exception;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class ValidationException extends \Exception implements Exception
{
    /**
     * @var ConstraintViolationListInterface
     */
    private $violations;

    /**
     * @var integer
     */
    private $lineNumber;

    /**
     * @param ConstraintViolationListInterface $list
     * @param integer                          $line
     */
    public function __construct(ConstraintViolationListInterface $list, $line)
    {
        $this->violations = $list;
        $this->lineNumber = $line;
    }

    /**
     * @return ConstraintViolationListInterface
     */
    public function getViolations()
    {
        return $this->violations;
    }

    /**
     * @return integer
     */
    public function getLineNumber()
    {
        return $this->lineNumber;
    }
}
