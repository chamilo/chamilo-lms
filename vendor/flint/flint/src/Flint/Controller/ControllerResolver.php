<?php

namespace Flint\Controller;

use Flint\Application;
use Flint\PimpleAwareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

/**
 * Injects the Application into the Controller if it implements the right interface
 * otherwise it delegates to the composed resolver.
 *
 * @package Flint
 */
class ControllerResolver implements ControllerResolverInterface
{
    protected $pimple;
    protected $resolver;

    /**
     * @param ControllerResolverInterface $resolver
     * @param Pimple                      $pimple
     */
    public function __construct(ControllerResolverInterface $resolver, \Pimple $pimple)
    {
        $this->resolver = $resolver;
        $this->pimple = $pimple;
    }

    /**
     * {@inheritDoc}
     */
    public function getController(Request $request)
    {
        $controller = $this->resolver->getController($request);

        if (false == is_array($controller)) {
            return $controller;
        }

        if ($controller[0] instanceof PimpleAwareInterface) {
            $controller[0]->setPimple($this->pimple);
        }

        return $controller;
    }

    /**
     * {@inheritDoc}
     */
    public function getArguments(Request $request, $controller)
    {
        return $this->resolver->getArguments($request, $controller);
    }
}
