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

namespace JMS\SecurityExtraBundle\Metadata\Driver;

use JMS\SecurityExtraBundle\Exception\InvalidArgumentException;
use Doctrine\Common\Annotations\Reader;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use JMS\SecurityExtraBundle\Annotation\SatisfiesParentSecurityPolicy;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use JMS\SecurityExtraBundle\Annotation\SecureReturn;
use JMS\SecurityExtraBundle\Metadata\ClassMetadata;
use JMS\SecurityExtraBundle\Metadata\MethodMetadata;
use Metadata\Driver\DriverInterface;
use \ReflectionClass;
use \ReflectionMethod;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;

/**
 * Loads security annotations and converts them to metadata
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class AnnotationDriver implements DriverInterface
{
    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function loadMetadataForClass(ReflectionClass $reflection)
    {
        $metadata = new ClassMetadata($reflection->name);

        $classPreAuthorize = $this->reader->getClassAnnotation($reflection, 'JMS\SecurityExtraBundle\Annotation\PreAuthorize');
        $classAnnotations = $this->reader->getClassAnnotations($reflection);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {
            // check if the method was defined on this class
            if ($method->getDeclaringClass()->name !== $reflection->name) {
                continue;
            }

            $annotations = $this->reader->getMethodAnnotations($method);
            if ($classAnnotations) {
                foreach ($classAnnotations as $classAnnotation) {
                    if ($classAnnotation instanceof SecureParam) {
                        $annotations[] = $classAnnotation;
                    }
                }
            }

            if (! $annotations && ! $classPreAuthorize) {
                continue;
            }

            if (null !== $methodMetadata = $this->convertMethodAnnotations($method, $annotations, $classPreAuthorize)) {
                $metadata->addMethodMetadata($methodMetadata);
            }
        }

        return $metadata;
    }

    private function convertMethodAnnotations(\ReflectionMethod $method, array $annotations, PreAuthorize $classPreAuthorize = null)
    {
        $parameters = array();
        foreach ($method->getParameters() as $index => $parameter) {
            $parameters[$parameter->getName()] = $index;
        }

        $methodMetadata = new MethodMetadata($method->class, $method->name);
        $hasSecurityMetadata = $hasPreRestrictions = false;
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Secure) {
                $methodMetadata->roles = $annotation->roles;
                $hasSecurityMetadata = $hasPreRestrictions = true;
            } elseif ($annotation instanceof PreAuthorize) {
                $methodMetadata->roles = array(new Expression($annotation->expr));
                $hasSecurityMetadata = $hasPreRestrictions = true;
            } elseif ($annotation instanceof SecureParam) {
                if (!isset($parameters[$annotation->name])) {
                    throw new InvalidArgumentException(sprintf('The parameter "%s" does not exist for method "%s".', $annotation->name, $method->name));
                }

                $methodMetadata->addParamPermissions($parameters[$annotation->name], $annotation->permissions);
                $hasSecurityMetadata = $hasPreRestrictions = true;
            } elseif ($annotation instanceof SecureReturn) {
                $methodMetadata->returnPermissions = $annotation->permissions;
                $hasSecurityMetadata = true;
            } elseif ($annotation instanceof SatisfiesParentSecurityPolicy) {
                $methodMetadata->satisfiesParentSecurityPolicy = true;
                $hasSecurityMetadata = true;
            } elseif ($annotation instanceof RunAs) {
                $methodMetadata->runAsRoles = $annotation->roles;
                $hasSecurityMetadata = true;
            }
        }

        // We use the following conditions to determine whether we should apply
        // a class-level @PreAuthorize annotation:
        //
        //    - No other authorization that runs before the method invocation
        //      must be configured. @Secure, @SecureParam, @PreAuthorize must
        //      not be present; @SecureReturn would be fine though.
        //
        //    - The method must be public, or alternatively publicOnly on
        //      @PreAuthorize must be set to false.
        if ( ! $hasPreRestrictions && $classPreAuthorize
                && (!$classPreAuthorize->publicOnly || !$method->isProtected())) {
            $methodMetadata->roles = array(new Expression($classPreAuthorize->expr));
            $hasSecurityMetadata = true;
        }

        return $hasSecurityMetadata ? $methodMetadata : null;
    }
}
