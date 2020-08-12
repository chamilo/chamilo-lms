<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Block;

use Sonata\BlockBundle\Block\Service\EditableBlockService;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\CoreBundle\Validator\ErrorElement;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @final since sonata-project/block-bundle 3.0
 */
class BlockServiceManager implements BlockServiceManagerInterface
{
    /**
     * @var array
     */
    protected $services;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var bool
     */
    protected $inValidate;

    /**
     * @var array
     */
    protected $contexts;

    public function __construct(ContainerInterface $container)
    {
        $this->services = [];
        $this->contexts = [];
        $this->container = $container;
    }

    public function get(BlockInterface $block)
    {
        $this->load($block->getType());

        return $this->services[$block->getType()];
    }

    public function getService($id)
    {
        return $this->load($id);
    }

    public function has($id)
    {
        return isset($this->services[$id]) ? true : false;
    }

    public function add($name, $service, $contexts = [])
    {
        $this->services[$name] = $service;

        foreach ($contexts as $context) {
            if (!\array_key_exists($context, $this->contexts)) {
                $this->contexts[$context] = [];
            }

            $this->contexts[$context][] = $name;
        }
    }

    public function setServices(array $blockServices)
    {
        foreach ($blockServices as $name => $service) {
            $this->add($name, $service);
        }
    }

    public function getServices()
    {
        foreach ($this->services as $name => $id) {
            if (\is_string($id)) {
                $this->load($id);
            }
        }

        return $this->sortServices($this->services);
    }

    public function getServicesByContext($context, $includeContainers = true)
    {
        if (!\array_key_exists($context, $this->contexts)) {
            return [];
        }

        $services = [];

        $containers = $this->container->getParameter('sonata.block.container.types');

        foreach ($this->contexts[$context] as $name) {
            if (!$includeContainers && \in_array($name, $containers, true)) {
                continue;
            }

            $services[$name] = $this->getService($name);
        }

        return $this->sortServices($services);
    }

    public function getLoadedServices()
    {
        $services = [];

        foreach ($this->services as $service) {
            if (!$service instanceof BlockServiceInterface) {
                continue;
            }

            $services[] = $service;
        }

        return $services;
    }

    /**
     * @todo: this function should be remove into a proper statefull object
     *
     * {@inheritdoc}
     */
    public function validate(ErrorElement $errorElement, BlockInterface $block)
    {
        if (!$block->getId() && !$block->getType()) {
            return;
        }

        if ($this->inValidate) {
            return;
        }

        // As block can be nested, we only need to validate the main block, no the children
        try {
            $this->inValidate = true;

            $blockService = $this->get($block);

            if ($blockService instanceof EditableBlockService) {
                $blockService->validate($errorElement, $block);
            } else {
                // NEXT_MAJOR: Remove this case
                $this->get($block)->validateBlock($errorElement, $block);
            }

            $this->inValidate = false;
        } catch (\Exception $e) {
            $this->inValidate = false;
        }
    }

    /**
     * @param string $type
     *
     * @throws \RuntimeException
     *
     * @return BlockServiceInterface
     */
    private function load($type)
    {
        if (!$this->has($type)) {
            throw new \RuntimeException(sprintf('The block service `%s` does not exist', $type));
        }

        if (!$this->services[$type] instanceof BlockServiceInterface) {
            $this->services[$type] = $this->container->get($type);
        }

        if (!$this->services[$type] instanceof BlockServiceInterface) {
            throw new \RuntimeException(sprintf('The service %s does not implement BlockServiceInterface', $type));
        }

        return $this->services[$type];
    }

    /**
     * Sort alphabetically services.
     *
     * @param array $services
     *
     * @return array
     */
    private function sortServices($services)
    {
        uasort($services, static function ($a, $b) {
            if ($a->getName() === $b->getName()) {
                return 0;
            }

            return ($a->getName() < $b->getName()) ? -1 : 1;
        });

        return $services;
    }
}
