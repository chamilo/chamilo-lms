<?php

namespace JMS\DiExtraBundle\Tests\Functional\Bundle\TestBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("controller.extended_hello", parent = "controller.hello")
 */
class ExtendedServiceController extends ServiceController
{
    public function helloAction()
    {
        return new Response('hello');
    }
}
