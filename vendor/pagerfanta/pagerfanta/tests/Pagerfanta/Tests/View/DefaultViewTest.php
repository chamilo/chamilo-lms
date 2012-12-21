<?php

namespace Pagerfanta\Tests\View;

use Pagerfanta\View\DefaultView;

class DefaultViewTest extends \PHPUnit_Framework_TestCase
{
    public function testRender()
    {
        $pagerfanta = $this
            ->getMockBuilder('Pagerfanta\Pagerfanta')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $pagerfanta
            ->expects($this->any())
            ->method('getCurrentPage')
            ->will($this->returnValue(10))
        ;
        $pagerfanta
            ->expects($this->any())
            ->method('getNbPages')
            ->will($this->returnValue(100))
        ;


        $view = new DefaultView();
        $this->assertTrue(is_string($ups = $view->render($pagerfanta, function($page) {
            return $page;
        })));
    }
}
