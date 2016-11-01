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
use Symfony\Component\Form\FormEvents;

/**
 * @author Ahmet Akbana <ahmetakbana@gmail.com>
 */
class ResizeFormListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSubscribedEvents()
    {
        $events = ResizeFormListener::getSubscribedEvents();

        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $events);
        $this->assertSame('preSetData', $events[FormEvents::PRE_SET_DATA]);
        $this->assertArrayHasKey(FormEvents::PRE_SUBMIT, $events);
        $this->assertSame('preBind', $events[FormEvents::PRE_SUBMIT]);
        $this->assertArrayHasKey(FormEvents::SUBMIT, $events);
        $this->assertSame('onBind', $events[FormEvents::SUBMIT]);
    }

    public function testPreSetDataWithNullData()
    {
        $listener = new ResizeFormListener('form', array(), false, null);

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
        $form->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator());
        $form->expects($this->never())
            ->method('add');

        $event = new FormEvent($form, null);

        $listener->preSetData($event);
    }

    /**
     * @group legacy
     * NEXT_MAJOR: remove this method
     */
    public function testPreBindCallsPreSubmit()
    {
        $listener = new ResizeFormListener('form', array(), true, null);

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
        $form->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator());

        $event = new FormEvent($form, null);

        $listener->preBind($event);
    }

    public function testPreSetDataThrowsExceptionWithStringEventData()
    {
        $listener = new ResizeFormListener('form', array(), false, null);

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();

        $event = new FormEvent($form, '');

        $this->setExpectedException('Symfony\Component\Form\Exception\UnexpectedTypeException');

        $listener->preSetData($event);
    }

    public function testPreSetData()
    {
        $typeOptions = array(
            'default' => 'option',
        );

        $listener = new ResizeFormListener('form', $typeOptions, false, null);

        $options = array(
            'property_path' => '[baz]',
            'data' => 'caz',
            'default' => 'option',
        );

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
        $form->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator(array('foo' => 'bar')));
        $form->expects($this->once())
            ->method('remove')
            ->with('foo');
        $form->expects($this->once())
            ->method('add')
            ->with('baz', 'form', $options);

        $data = array('baz' => 'caz');

        $event = new FormEvent($form, $data);

        $listener->preSetData($event);
    }

    public function testPreSubmitWithResizeOnBindFalse()
    {
        $listener = new ResizeFormListener('form', array(), false, null);

        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')->disableOriginalConstructor()->getMock();
        $event->expects($this->never())
            ->method('getForm');

        $listener->preSubmit($event);
    }

    public function testPreSubmitDataWithNullData()
    {
        $listener = new ResizeFormListener('form', array(), true, null);

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
        $form->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator(array('foo' => 'bar')));
        $form->expects($this->never())
            ->method('has');

        $event = new FormEvent($form, null);

        $listener->preSubmit($event);
    }

    public function testPreSubmitThrowsExceptionWithIntEventData()
    {
        $listener = new ResizeFormListener('form', array(), true, null);

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();

        $event = new FormEvent($form, 123);

        $this->setExpectedException('Symfony\Component\Form\Exception\UnexpectedTypeException');

        $listener->preSubmit($event);
    }

    public function testPreSubmitData()
    {
        $typeOptions = array(
            'default' => 'option',
        );

        $listener = new ResizeFormListener('form', $typeOptions, true, null);

        $options = array(
            'property_path' => '[baz]',
            'default' => 'option',
        );

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
        $form->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator(array('foo' => 'bar')));
        $form->expects($this->once())
            ->method('remove')
            ->with('foo');
        $form->expects($this->once())
            ->method('add')
            ->with('baz', 'form', $options);

        $data = array('baz' => 'caz');

        $event = new FormEvent($form, $data);

        $listener->preSubmit($event);
    }

    public function testPreSubmitDataWithClosure()
    {
        $typeOptions = array(
            'default' => 'option',
        );

        $data = array('baz' => 'caz');

        $closure = function () use ($data) {
            return $data['baz'];
        };

        $listener = new ResizeFormListener('form', $typeOptions, true, $closure);

        $options = array(
            'property_path' => '[baz]',
            'default' => 'option',
            'data' => 'caz',
        );

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
        $form->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator(array('foo' => 'bar')));
        $form->expects($this->once())
            ->method('remove')
            ->with('foo');
        $form->expects($this->once())
            ->method('add')
            ->with('baz', 'form', $options);

        $event = new FormEvent($form, $data);

        $listener->preSubmit($event);
    }

    /**
     * @group legacy
     * NEXT_MAJOR: remove this method
     */
    public function testOnBindCallsOnSubmit()
    {
        $listener = new ResizeFormListener('form', array(), true, null);

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();

        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')->disableOriginalConstructor()->getMock();
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn(null);
        $event->expects($this->once())
            ->method('setData')
            ->with(array());

        $listener->onBind($event);
    }

    public function testOnSubmitWithResizeOnBindFalse()
    {
        $listener = new ResizeFormListener('form', array(), false, null);

        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')->disableOriginalConstructor()->getMock();
        $event->expects($this->never())
            ->method('getForm');

        $listener->onSubmit($event);
    }

    public function testOnSubmitDataWithNullData()
    {
        $listener = new ResizeFormListener('form', array(), true, null);

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
        $form->expects($this->never())
            ->method('has');

        $event = new FormEvent($form, null);

        $listener->onSubmit($event);
    }

    public function testOnSubmitThrowsExceptionWithIntEventData()
    {
        $listener = new ResizeFormListener('form', array(), true, null);

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();

        $event = new FormEvent($form, 123);

        $this->setExpectedException('Symfony\Component\Form\Exception\UnexpectedTypeException');

        $listener->onSubmit($event);
    }

    public function testOnSubmit()
    {
        $listener = new ResizeFormListener('form', array(), true, null);

        $reflector = new \ReflectionClass('Sonata\CoreBundle\Form\EventListener\ResizeFormListener');
        $reflectedMethod = $reflector->getProperty('removed');
        $reflectedMethod->setAccessible(true);
        $reflectedMethod->setValue($listener, array('foo'));

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
        $form->expects($this->at(2))
            ->method('has')
            ->with('baz')
            ->willReturn(true);

        $data = array(
            'foo' => 'foo-value',
            'bar' => 'bar-value',
            'baz' => 'baz-value',
        );

        $removedData = array(
            'baz' => 'baz-value',
        );

        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')->disableOriginalConstructor()->getMock();
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($data);
        $event->expects($this->once())
            ->method('setData')
            ->with($removedData);

        $listener->onSubmit($event);
    }
}
