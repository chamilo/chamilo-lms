<?php

namespace JMS\DiExtraBundle\Tests\Functional\Bundle\TestBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

abstract class ServiceController
{
    public function __construct(RouterInterface $router)
    {
        // Dummy constructor injection, to make sure inheritance works
    }
}
