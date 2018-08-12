<?php

namespace Knp\DoctrineBehaviors\ORM\Translatable;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\Container;

/**
 * @author     Florian Klein <florian.klein@free.fr>
 */
class CurrentLocaleCallable
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function __invoke()
    {
        if (!$this->container->has('request_stack')) {
            if(!$this->container->isScopeActive('request')) {
                return NULL;
            }
            $request = $this->container->get('request');

            return $request->getLocale();
        } else if ($request = $this->container->get('request_stack')->getCurrentRequest()) {
            return $request->getLocale();
        }

        return NULL;
    }
}

