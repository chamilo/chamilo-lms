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

namespace JMS\DiExtraBundle\Metadata\Driver;

use JMS\DiExtraBundle\Annotation\SecurityFunction;

use JMS\DiExtraBundle\Annotation\AfterSetup;

use JMS\DiExtraBundle\Annotation\FormType;

use JMS\DiExtraBundle\Annotation\AbstractDoctrineListener;
use JMS\DiExtraBundle\Annotation\DoctrineListener;
use JMS\DiExtraBundle\Annotation\DoctrineMongoDBListener;
use JMS\DiExtraBundle\Annotation\Reference as AnnotReference;
use JMS\DiExtraBundle\Annotation\LookupMethod;
use JMS\DiExtraBundle\Annotation\Validator;
use JMS\DiExtraBundle\Annotation\InjectParams;
use JMS\DiExtraBundle\Exception\InvalidTypeException;
use JMS\DiExtraBundle\Annotation\Observe;
use Doctrine\Common\Annotations\Reader;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\Service;
use JMS\DiExtraBundle\Annotation\Tag;
use JMS\DiExtraBundle\Metadata\ClassMetadata;
use Metadata\Driver\DriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

class AnnotationDriver implements DriverInterface
{
    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function loadMetadataForClass(\ReflectionClass $class)
    {
        $metadata = new ClassMetadata($className = $class->getName());
        if (false !== $filename = $class->getFilename()) {
            $metadata->fileResources[] = $filename;
        }

        // this is a bit of a hack, but avoids any timeout issues when a class
        // is moved into one of the compiled classes files, and Doctrine
        // Common 2.1 is used.
        if (false !== strpos($filename, '/classes.php')
            || false !== strpos($filename, '/bootstrap.php')) {
            return null;
        }

        foreach ($this->reader->getClassAnnotations($class) as $annot) {
            if ($annot instanceof Service) {
                if (null === $annot->id) {
                    $metadata->id = $this->generateId($className);
                } else {
                    $metadata->id = $annot->id;
                }

                $metadata->parent = $annot->parent;
                $metadata->public = $annot->public;
                $metadata->scope = $annot->scope;
                $metadata->abstract = $annot->abstract;
            } else if ($annot instanceof Tag) {
                $metadata->tags[$annot->name][] = $annot->attributes;
            } else if ($annot instanceof Validator) {
                // automatically register as service if not done explicitly
                if (null === $metadata->id) {
                    $metadata->id = $this->generateId($className);
                }

                $metadata->tags['validator.constraint_validator'][] = array(
                    'alias' => $annot->alias,
                );
            } else if ($annot instanceof AbstractDoctrineListener) {
                if (null === $metadata->id) {
                    $metadata->id = $this->generateId($className);
                }

                foreach ($annot->events as $event) {
                    $metadata->tags[$annot->getTag()][] = array(
                        'event' => $event,
                        'connection' => $annot->connection ?: 'default',
                        'lazy' => $annot->lazy,
                        'priority' => $annot->priority,
                    );
                }
            } else if ($annot instanceof FormType) {
                if (null === $metadata->id) {
                    $metadata->id = $this->generateId($className);
                }

                $alias = $annot->alias;

                // try to extract it from the class itself
                if (null === $alias) {
                    $instance = unserialize(sprintf('O:%d:"%s":0:{}', strlen($className), $className));
                    $alias = $instance->getName();
                }

                $metadata->tags['form.type'][] = array(
                    'alias' => $alias,
                );
            }
        }

        $hasInjection = false;
        foreach ($class->getProperties() as $property) {
            if ($property->getDeclaringClass()->getName() !== $className) {
                continue;
            }
            $name = $property->getName();

            foreach ($this->reader->getPropertyAnnotations($property) as $annot) {
                if ($annot instanceof Inject) {
                    $hasInjection = true;
                    $metadata->properties[$name] = $this->convertReferenceValue($name, $annot);
                }
            }
        }

        foreach ($class->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() !== $className) {
                continue;
            }
            $name = $method->getName();

            foreach ($this->reader->getMethodAnnotations($method) as $annot) {
                if ($annot instanceof Observe) {
                    $metadata->tags['kernel.event_listener'][] = array(
                        'event' => $annot->event,
                        'method' => $name,
                        'priority' => $annot->priority,
                    );
                } else if ($annot instanceof SecurityFunction) {
                    $metadata->tags['security.expressions.function_evaluator'][] = array(
                        'function' => $annot->function,
                        'method' => $name,
                    );
                } else if ($annot instanceof InjectParams) {
                    $params = array();
                    foreach ($method->getParameters() as $param) {
                        if (!isset($annot->params[$paramName = $param->getName()])) {
                            $params[] = $this->convertReferenceValue($paramName, new Inject(array('value' => null)));
                            continue;
                        }

                        $params[] = $this->convertReferenceValue($paramName, $annot->params[$paramName]);
                    }

                    if (!$params) {
                        continue;
                    }

                    $hasInjection = true;

                    if ('__construct' === $name) {
                        $metadata->arguments = $params;
                    } else {
                        $metadata->methodCalls[] = array($name, $params);
                    }
                } else if ($annot instanceof LookupMethod) {
                    $hasInjection = true;

                    if ($method->isFinal()) {
                        throw new \RuntimeException(sprintf('The method "%s::%s" is marked as final and cannot be declared as lookup-method.', $className, $name));
                    }
                    if ($method->isPrivate()) {
                        throw new \RuntimeException(sprintf('The method "%s::%s" is marked as private and cannot be declared as lookup-method.', $className, $name));
                    }
                    if ($method->getParameters()) {
                        throw new \RuntimeException(sprintf('The method "%s::%s" must have a no-arguments signature if you want to use it as lookup-method.', $className, $name));
                    }

                    $metadata->lookupMethods[$name] = $this->convertReferenceValue('get' === substr($name, 0, 3) ? substr($name, 3) : $name, $annot);
                } else if ($annot instanceof AfterSetup) {
                    if (!$method->isPublic()) {
                        throw new \RuntimeException(sprintf('The init method "%s::%s" must be public.', $method->class, $method->name));
                    }

                    $metadata->initMethod = $method->name;
                }
            }
        }

        if (null == $metadata->id && !$hasInjection) {
            return null;
        }

        return $metadata;
    }

    private function convertReferenceValue($name, AnnotReference $annot)
    {
        if (null === $annot->value) {
            return new Reference($this->generateId($name), false !== $annot->required ? ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE : ContainerInterface::NULL_ON_INVALID_REFERENCE, $annot->strict);
        }

        if (false === strpos($annot->value, '%')) {
            return new Reference($annot->value, false !== $annot->required ? ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE : ContainerInterface::NULL_ON_INVALID_REFERENCE, $annot->strict);
        }

        return $annot->value;
    }

    private function generateId($name)
    {
        $name = preg_replace('/(?<=[a-zA-Z0-9])[A-Z]/', '_\\0', $name);

        return strtolower(strtr($name, '\\', '.'));
    }
}
