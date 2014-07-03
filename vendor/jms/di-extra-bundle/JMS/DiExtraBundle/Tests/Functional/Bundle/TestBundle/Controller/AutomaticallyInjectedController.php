<?php

namespace JMS\DiExtraBundle\Tests\Functional\Bundle\TestBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Routing\RouterInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class AutomaticallyInjectedController
{
    private $context;
    private $templating;
    private $router;
    private $foo;

    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @Route("/automatic-controller-injection-test")
     */
    public function testAction()
    {
        $content = '';

        $content .= sprintf("\$context injection: %s\n", $this->context instanceof SecurityContextInterface ? 'OK' : 'FAILED');
        $content .= sprintf("\$templating injection: %s\n", $this->templating instanceof EngineInterface ? 'OK' : 'FAILED');
        $content .= sprintf("\$router injection: %s\n", $this->router instanceof RouterInterface ? 'OK' : 'FAILED');
        $content .= sprintf("\$foo injection: %s\n", 'bar' === $this->foo ? 'OK' : 'FAILED');

        return new Response($content);
    }
}
