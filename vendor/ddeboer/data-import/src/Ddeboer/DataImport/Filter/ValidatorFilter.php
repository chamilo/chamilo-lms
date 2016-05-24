<?php

namespace Ddeboer\DataImport\Filter;

use Symfony\Component\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints;
use Ddeboer\DataImport\Exception\ValidationException;

class ValidatorFilter implements FilterInterface
{
    private $validator;

    private $throwExceptions = false;

    private $line = 1;

    private $strict = true;

    private $constraints = array();

    private $violations = array();

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function add($field, Constraint $constraint)
    {
        if (!isset($this->constraints[$field])) {
            $this->constraints[$field] = array();
        }

        $this->constraints[$field][] = $constraint;
    }

    public function throwExceptions($flag = true)
    {
        $this->throwExceptions = $flag;
    }

    public function setStrict($strict)
    {
        $this->strict = $strict;
    }

    public function getViolations()
    {
        return $this->violations;
    }

    public function filter(array $item)
    {
        if (!$this->strict) {
            // Only validate properties which have an constaint.
            $temp = array_intersect(array_keys($item), array_keys($this->constraints));
            $item = array_intersect_key($item, array_flip($temp));
        }

        $constraints = new Constraints\Collection($this->constraints);
        $list = $this->validator->validateValue($item, $constraints);
        $currentLine = $this->line++;

        if (count($list) > 0) {
            $this->violations[$currentLine] = $list;

            if ($this->throwExceptions) {
                throw new ValidationException($list, $currentLine);
            }
        }

        return 0 === count($list);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 256;
    }
}
