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
            'sm_can' => new \Twig_Function_Method($this, 'can'),
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
     * @{inheritDoc}
     */
    public function getName()
    {
        return 'sm';
    }
}
