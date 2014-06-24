<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\SecurityExtraBundle\Security\Authorization\Interception;

use CG\Core\ClassUtils;
use Metadata\MetadataFactoryInterface;
use JMS\AopBundle\Aop\PointcutInterface;

class SecurityPointcut implements PointcutInterface
{
    private $metadataFactory;
    private $secureAllServices;
    private $securedClasses = array();
    private $patterns;

    public function __construct(MetadataFactoryInterface $metadataFactory, $secureAllServices = false, array $patterns = array())
    {
        $this->metadataFactory = $metadataFactory;
        $this->secureAllServices = $secureAllServices;
        $this->patterns = $patterns;
    }

    public function setSecuredClasses(array $classes)
    {
        $this->securedClasses = $classes;
    }

    public function matchesClass(\ReflectionClass $class)
    {
        if ($this->secureAllServices) {
            return true;
        }

        if ('Controller' === substr(ClassUtils::getUserClass($class->name), -10)) {
            return true;
        }

        foreach ($this->patterns as $pattern => $expr) {
            // if not for all patterns the class is specified, then we need to scan all
            // classes to catch all methods
            if (false === $pos = strpos($pattern, '::')) {
                // controller notation is already checked by JMSDiExtraBundle,
                // we can safely ignore these patterns here
                if (2 === substr_count($pattern, ':')) {
                    continue;
                }

                return true;
            }

            if (0 < preg_match('#'.substr($pattern, 0, $pos).'$#', $class->name)) {
                return true;
            }
        }

        foreach ($this->securedClasses as $securedClass) {
            if ($class->name === $securedClass || $class->isSubclassOf($securedClass)) {
                return true;
            }
        }

        return false;
    }

    public function matchesMethod(\ReflectionMethod $method)
    {
        $userClass = ClassUtils::getUserClass($method->class);
        $metadata = $this->metadataFactory->getMetadataForClass($userClass);

        if (null === $metadata) {
            return false;
        }

        return isset($metadata->methodMetadata[$method->name]);
    }
}
