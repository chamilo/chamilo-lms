<?php

namespace JMS\SecurityExtraBundle\Tests\Functional\TestBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

class FooController
{
    public function exceptionAction()
    {
        return new Response();
    }

    public function fooAction()
    {
        return new Response();
    }

    public function barAction()
    {
        return new Response();
    }
}