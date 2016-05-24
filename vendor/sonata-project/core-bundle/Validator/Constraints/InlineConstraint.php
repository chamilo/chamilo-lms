<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint which allows inline-validation inside services.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
class InlineConstraint extends Constraint
{
    /**
     * @var mixed
     */
    protected $service;

    /**
     * @var mixed
     */
    protected $method;

    /**
     * @var mixed
     */
    protected $serializingWarning;

    /**
     * {@inheritdoc}
     */
    public function __construct($options = null)
    {
        parent::__construct($options);

        if ((!is_string($this->service) || !is_string($this->method)) && $this->serializingWarning !== true) {
            throw new \RuntimeException('You are using a closure with the `InlineConstraint`, this constraint'.
                ' cannot be serialized. You need to re-attach the `InlineConstraint` on each request.'.
                ' Once done, you can set the `serializingWarning` option to `true` to avoid this message.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'sonata.core.validator.inline';
    }

    /**
     * @return bool
     */
    public function isClosure()
    {
        return $this->method instanceof \Closure;
    }

    /**
     * @return mixed
     */
    public function getClosure()
    {
        return $this->method;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions()
    {
        return array(
            'service',
            'method',
        );
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return mixed
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return mixed
     */
    public function getSerializingWarning()
    {
        return $this->serializingWarning;
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        if (!is_string($this->service) || !is_string($this->method)) {
            return array();
        }

        // Initialize "groups" option if it is not set
        $this->groups;

        return array_keys(get_object_vars($this));
    }

    /**
     * {@inheritdoc}
     */
    public function __wakeup()
    {
        if (is_string($this->service) && is_string($this->method)) {
            return;
        }

        $this->method = function () {
        };

        $this->serializingWarning = true;
    }
}
