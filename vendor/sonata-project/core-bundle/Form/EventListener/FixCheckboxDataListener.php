<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\DataTransformer\BooleanToStringTransformer;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Using BooleanToStringTransform in a checkbox form type
 * will set false value to '0' instead of null which will end up
 * returning true value when the form is bind.
 *
 * Class FixCheckboxDataListener
 *
 * @author  Sylvain Rascar <rascar.sylvain@gmail.com>
 */
class FixCheckboxDataListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(FormEvents::PRE_SUBMIT => 'preBind');
    }

    /**
     * @param FormEvent $event
     *
     * @deprecated Since version 2.3, to be renamed in 4.0.
     *             Use {@link preSubmit} instead
     */
    public function preBind(FormEvent $event)
    {
        // BC prevention for class extending this one.
        if (get_called_class() !== 'Sonata\CoreBundle\Form\EventListener\FixCheckboxDataListener') {
            @trigger_error('The '.__METHOD__.' is deprecated since 2.3 and will be renamed in 4.0. Use '.__CLASS__.'::preSubmit instead.', E_USER_DEPRECATED);
        }

        $this->preSubmit($event);
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $transformers = $event->getForm()->getConfig()->getViewTransformers();

        if (count($transformers) === 1 && $transformers[0] instanceof BooleanToStringTransformer && $data === '0') {
            $event->setData(null);
        }
    }
}
