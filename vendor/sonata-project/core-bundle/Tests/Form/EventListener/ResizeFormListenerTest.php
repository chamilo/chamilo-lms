<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\Form\EventListener;

use Sonata\CoreBundle\Form\EventListener\ResizeFormListener;
use Symfony\Component\Form\FormEvent;

/**
 * Class ResizeFormListenerTest.
 *
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class ResizeFormListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testPreBindClosure()
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();

        $value = array('value1', 'value2');
        $data  = array($value);

        $event = new FormEvent($form, $data);

        $closure = function ($listenerValue) use ($value) {
            $this->assertSame($value, $listenerValue);
        };

        $listener = new ResizeFormListener('form', array(), false, $closure);

        $listener->preBind($event);
    }
}
