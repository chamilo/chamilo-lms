<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Form\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormTypeGuesserChain;
use Symfony\Component\Form\FormTypeGuesserInterface;

/**
 * This proxy class help to keep BC code with < SF2.8 form behavior by restoring
 * the type as a code and not as a class.
 */
class DependencyInjectionExtension implements FormExtensionInterface
{
    /**
     * @var FormExtensionInterface
     */
    protected $extension;

    protected $mappingTypes;

    protected $extensionTypes;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var []string
     */
    private $typeServiceIds;

    /**
     * @var []string
     */
    private $typeExtensionServiceIds;

    /**
     * @var []string
     */
    private $guesserServiceIds;

    /**
     * @var FormTypeGuesserInterface
     */
    private $guesser;
    private $guesserLoaded = false;

    /**
     * DependencyInjectionExtension constructor.
     *
     * @param ContainerInterface $container
     * @param array              $typeServiceIds
     * @param array              $typeExtensionServiceIds
     * @param array              $guesserServiceIds
     * @param array              $mappingTypes
     * @param array              $extensionTypes
     */
    public function __construct(ContainerInterface $container, array $typeServiceIds, array $typeExtensionServiceIds, array $guesserServiceIds, array $mappingTypes = array(), array $extensionTypes = array())
    {
        $this->container = $container;
        $this->typeServiceIds = $typeServiceIds;
        $this->typeExtensionServiceIds = $typeExtensionServiceIds;
        $this->guesserServiceIds = $guesserServiceIds;

        $this->mappingTypes = $mappingTypes;
        $this->mappingExtensionTypes = $extensionTypes;

        $this->reverseMappingTypes = array_flip($mappingTypes);
    }

    /**
     * {@inheritdoc}
     */
    public function getType($name)
    {
        // resolve code to FQCN
        $name = self::findClass($this->mappingTypes, $name);

        if (!isset($this->typeServiceIds[$name])) {
            if (class_exists($name) && in_array('Symfony\Component\Form\FormTypeInterface', class_implements($name), true)) {
                return new $name();
            }
            throw new InvalidArgumentException(sprintf('The field type "%s" is not registered with the service container.', $name));
        }

        $type = $this->container->get($this->typeServiceIds[$name]);

        if ($name !== get_class($type) && (method_exists($type, 'getName') && $type->getName() !== $name)) {
            throw new InvalidArgumentException(
                sprintf('The type name specified for the service "%s" does not match the actual name. Expected "%s", given "%s"',
                    $this->typeServiceIds[$name],
                    $name,
                    get_class($type)
                ));
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function hasType($name)
    {
        return isset($this->mappingTypes[$name]) || isset($this->typeServiceIds[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeExtensions($name)
    {
        // lookup inside the extension mapping
        $serviceIdx = array();

        if (isset($this->reverseMappingTypes[$name])) {
            $code = $this->reverseMappingTypes[$name];

            if (isset($this->mappingExtensionTypes[$code])) {
                $serviceIdx = array_merge($serviceIdx, $this->mappingExtensionTypes[$code]);
            }
        }

        $serviceIdx = array_unique(array_merge(isset($this->typeExtensionServiceIds[$name]) ? $this->typeExtensionServiceIds[$name] : array(), $serviceIdx));

        $extensions = array();
        foreach ($serviceIdx as $serviceId) {
            if ($this->container->has($serviceId)) {
                $extensions[] = $this->container->get($serviceId);
            }
        }

        return $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function hasTypeExtensions($name)
    {
        return isset($this->reverseMappingTypes[$name]) || isset($this->typeExtensionServiceIds[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeGuesser()
    {
        if (!$this->guesserLoaded) {
            $this->guesserLoaded = true;
            $guessers = array();

            foreach ($this->guesserServiceIds as $serviceId) {
                if ($this->container->has($serviceId)) {
                    $guessers[] = $this->container->get($serviceId);
                }
            }

            if ($guessers) {
                $this->guesser = new FormTypeGuesserChain($guessers);
            }
        }

        return $this->guesser;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected static function findClass($mapping, $type)
    {
        if (strpos($type, '\\')) {
            return $type;
        }

        if (!isset($mapping[$type])) {
            return $type;
        }

        return $mapping[$type];
    }
}
