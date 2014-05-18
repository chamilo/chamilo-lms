<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Component\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;

class ConstraintValidatorFactory implements ConstraintValidatorFactoryInterface
{
    protected $validators = array();

    /**
     * @param $class_name
     * @param ConstraintValidator $validator
     */
    public function addInstance($class_name, ConstraintValidator $validator)
    {
        $this->validators[$class_name] = $validator;
    }

    /**
     * @param Constraint $constraint
     * @return \Symfony\Component\Validator\ConstraintValidatorInterface
     */
    public function getInstance(Constraint $constraint)
    {
        $class_name = $constraint->validatedBy();

        if (!isset($this->validators[$class_name])) {
            $this->validators[$class_name] = new $class_name();
        }

        return $this->validators[$class_name];
    }
}
