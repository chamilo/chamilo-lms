<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Request;

use FOS\RestBundle\Request\RequestBodyParamConverter20 as BaseRequestBodyParamConverter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ValidatorInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;

/**
 * Defines correct class to use (entity or document) in param converter configuration
 *
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class RequestBodyParamConverter extends BaseRequestBodyParamConverter
{
    /**
     * @var array
     */
    protected $classes;

    /**
     * Constructor
     *
     * @param object             $serializer
     * @param array|null         $groups     An array of groups to be used in the serialization context
     * @param string|null        $version    A version string to be used in the serialization context
     * @param object             $serializer
     * @param ValidatorInterface $validator
     * @param string|null        $validationErrorsArgument
     * @param array              $classes    Classes (entities or documents) to use
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($serializer, $groups = null, $version = null, ValidatorInterface $validator = null, $validationErrorsArgument = null, array $classes)
    {
        $this->classes = $classes;

        parent::__construct($serializer, $groups, $version, $validator, $validationErrorsArgument);
    }

    /**
     * {@inheritDoc}
     */
    public function apply(Request $request, ConfigurationInterface $configuration)
    {
        $this->setConfigurationClass($configuration);

        return $this->execute($request, $configuration);
    }

    /**
     * Sets correct configuration class depending on available classes
     *
     * @param ConfigurationInterface $configuration
     */
    protected function setConfigurationClass(ConfigurationInterface $configuration)
    {
        foreach ($this->classes as $class) {
            $reflectionClass = new \ReflectionClass($class);

            if ($reflectionClass->isSubclassOf($configuration->getClass())) {
                $configuration->setClass($class);
                break;
            }
        }
    }
}
