<?php

namespace Chamilo\UserBundle\Form\EventListener;

use Sylius\Bundle\AttributeBundle\Form\EventListener\BuildAttributeFormChoicesListener as BaseBuildAttributeFormChoicesListener;
use Sylius\Component\Attribute\Model\AttributeTypes;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Form event listener that builds choices for attribute form.
 *
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 * @author Liverbool <liverbool@gmail.com>
 */
class BuildAttributeFormChoicesListener extends BaseBuildAttributeFormChoicesListener
{
    /**
     * Form factory.
     *
     * @var FormFactoryInterface
     */
    private $factory;

    /**
     * Constructor.
     *
     * @param FormFactoryInterface $factory
     */
    public function __construct(FormFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'buildChoices',
            FormEvents::PRE_SUBMIT => 'buildConfiguration',
        ];
    }

    /**
     * Build configuration field for attribute form.
     *
     * @param FormEvent $event
     */
    public function buildConfiguration(FormEvent $event)
    {
        $data = $event->getData();
        $choices = [];

        if (AttributeTypes::CHOICE === $data['type'] && !empty($data['choices'])) {
            $choices = $data['choices'];
        }

        $data['configuration'] = $choices;

        if (!$event->getForm()->has('configuration')) {
            $event->getForm()->add(
                $this->factory->createNamed(
                    'configuration',
                    'collection',
                    null,
                    [
                        'allow_add' => true,
                        'allow_delete' => true,
                        'by_reference' => false,
                        'auto_initialize' => false,
                    ]
                )
            );
        }

        $event->setData($data);
    }

    /**
     * Builds choices for attribute form.
     *
     * @param FormEvent $event
     */
    public function buildChoices(FormEvent $event)
    {
        $attribute = $event->getData();
        if (null === $attribute) {
            return;
        }

        $type = $attribute->getType();

        if (null === $type || AttributeTypes::CHOICE === $type) {
            $data = null;
            $config = $attribute->getConfiguration();

            if (!empty($config['choices'])) {
                $data = $config['choices'];
            }

            $event->getForm()->add(
                $this->factory->createNamed(
                    'choices',
                    'collection',
                    null,
                    [
                        'type' => 'text',
                        'allow_add' => true,
                        'allow_delete' => true,
                        'by_reference' => false,
                        'auto_initialize' => false,
                        'mapped' => false,
                        'data' => $data,
                    ]
                )
            );
        }
    }
}
