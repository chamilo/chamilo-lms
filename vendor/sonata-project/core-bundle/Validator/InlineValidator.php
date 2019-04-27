<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Validator;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;

class InlineValidator extends ConstraintValidator
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ConstraintValidatorFactoryInterface
     */
    protected $constraintValidatorFactory;

    /**
     * @param ContainerInterface                  $container
     * @param ConstraintValidatorFactoryInterface $constraintValidatorFactory
     */
    public function __construct(ContainerInterface $container, ConstraintValidatorFactoryInterface $constraintValidatorFactory)
    {
        $this->container = $container;
        $this->constraintValidatorFactory = $constraintValidatorFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($constraint->isClosure()) {
            $function = $constraint->getClosure();
        } else {
            if (is_string($constraint->getService())) {
                $service = $this->container->get($constraint->getService());
            } else {
                $service = $constraint->getService();
            }

            $function = array($service, $constraint->getMethod());
        }

        call_user_func($function, $this->getErrorElement($value), $value);
    }

    /**
     * @param mixed $value
     *
     * @return ErrorElement
     */
    protected function getErrorElement($value)
    {
        return new ErrorElement(
            $value,
            $this->constraintValidatorFactory,
            $this->context,
            $this->context->getGroup()
        );
    }
}
