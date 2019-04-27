<?php

/*
 * This file is part of the StateMachine package.
 *
 * (c) Alexandre Bacco
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SM\Factory;

use SM\SMException;
use SM\StateMachine\StateMachineInterface;

abstract class AbstractFactory implements ClearableFactoryInterface
{
    /**
     * @var array
     */
    protected $configs;

    /**
     * @var array
     */
    protected $stateMachines = array();

    /**
     * @param array $configs Array of configs for the available state machines
     */
    public function __construct(array $configs)
    {
        foreach ($configs as $graph => $config) {
            $this->addConfig($config, $graph);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get($object, $graph = 'default')
    {
        $hash = spl_object_hash($object);

        if (isset($this->stateMachines[$hash][$graph])) {
            return $this->stateMachines[$hash][$graph];
        }

        foreach ($this->configs as $config) {
            if ($config['graph'] === $graph && $object instanceof $config['class']) {
                return $this->stateMachines[$hash][$graph] = $this->createStateMachine($object, $config);
            }
        }

        throw new SMException(sprintf(
            'Cannot create a state machine because the configuration for object "%s" with graph "%s" does not exist.',
            get_class($object),
            $graph
        ));
    }
    
    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        $this->stateMachines = array();
    }

    /**
     * Adds a new config
     *
     * @param array  $config
     * @param string $graph
     *
     * @throws SMException If the index "class" is not configured
     */
    public function addConfig(array $config, $graph = 'default')
    {
        if (!isset($config['graph'])) {
            $config['graph'] = $graph;
        }

        if (!isset($config['class'])) {
            throw new SMException(sprintf(
               'Index "class" needed for the state machine configuration of graph "%s"',
                $config['graph']
            ));
        }

        $this->configs[] = $config;
    }

    /**
     * Create a state machine for the given object and config
     *
     * @param       $object
     * @param array $config
     *
     * @return StateMachineInterface
     */
    abstract protected function createStateMachine($object, array $config);
}
