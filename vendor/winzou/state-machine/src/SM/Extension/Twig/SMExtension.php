<?php

/*
 * This file is part of the StateMachine package.
 *
 * (c) Alexandre Bacco
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SM\Extension\Twig;

use SM\Factory\FactoryInterface;

class SMExtension extends \Twig_Extension
{
    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @param FactoryInterface $factory
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @{inheritDoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('sm_can', array($this, 'can')),
            new \Twig_SimpleFunction('sm_state', array($this, 'getState')),
            new \Twig_SimpleFunction('sm_possible_transitions', array($this, 'getPossibleTransitions')),
        );
    }

    /**
     * @param object $object
     * @param string $transition
     * @param string $graph
     *
     * @return bool
     */
    public function can($object, $transition, $graph = 'default')
    {
        return $this->factory->get($object, $graph)->can($transition);
    }

    /**
     * @param object $object
     * @param string $graph
     *
     * @return string
     */
    public function getState($object, $graph = 'default')
    {
        return $this->factory->get($object, $graph)->getState();
    }

    /**
     * @param object $object
     * @param string $graph
     *
     * @return array
     */
    public function getPossibleTransitions($object, $graph = 'default')
    {
        return $this->factory->get($object, $graph)->getPossibleTransitions();
    }

    /**
     * @{inheritDoc}
     */
    public function getName()
    {
        return 'sm';
    }
}
