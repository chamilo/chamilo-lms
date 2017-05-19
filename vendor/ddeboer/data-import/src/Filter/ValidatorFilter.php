<?php

namespace Ddeboer\DataImport\Filter;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints;
use Ddeboer\DataImport\Exception\ValidationException;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class ValidatorFilter
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var boolean
     */
    private $throwExceptions = false;

    /**
     * @var integer
     */
    private $line = 1;

    /**
     * @var boolean
     */
    private $strict = true;

    /**
     * @var array
     */
    private $constraints = [];

    /**
     * @var array
     */
    private $violations = [];

    /**
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param string     $field
     * @param Constraint $constraint
     */
    public function add($field, Constraint $constraint)
    {
        if (!isset($this->constraints[$field])) {
            $this->constraints[$field] = [];
        }

        $this->constraints[$field][] = $constraint;
    }

    /**
     * @param boolean $flag
     */
    public function throwExceptions($flag = true)
    {
        $this->throwExceptions = $flag;
    }

    /**
     * @param boolean $strict
     */
    public function setStrict($strict)
    {
        $this->strict = $strict;
    }

    /**
     * @return array
     */
    public function getViolations()
    {
        return $this->violations;
    }

    /**
     * @param array $item
     *
     * @return boolean
     */
    public function __invoke(array $item)
    {
        if (!$this->strict) {
            // Only validate properties which have an constaint.
            $temp = array_intersect(array_keys($item), array_keys($this->constraints));
            $item = array_intersect_key($item, array_flip($temp));
        }

        $constraints = new Constraints\Collection($this->constraints);
        $list = $this->validator->validate($item, $constraints);
        $currentLine = $this->line++;

        if (count($list) > 0) {
            $this->violations[$currentLine] = $list;

            if ($this->throwExceptions) {
                throw new ValidationException($list, $currentLine);
            }
        }

        return 0 === count($list);
    }
}
