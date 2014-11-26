<?php

namespace Chamilo\UserBundle\Form\EventListener;

use Sylius\Component\Attribute\Model\AttributeTypes;
use Sylius\Component\Attribute\Model\AttributeValueInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Sylius\Bundle\AttributeBundle\Form\EventListener\BuildAttributeValueFormListener as BaseBuildAttributeValueFormListener;

/**
 * Class BuildAttributeValueFormListener
 * @package Chamilo\UserBundle\Form\EventListener
 */
class BuildAttributeValueFormListener extends BaseBuildAttributeValueFormListener
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
     * Builds proper product form after setting the product.
     *
     * @param FormEvent $event
     */
    public function buildForm(FormEvent $event)
    {
        /** @var \Chamilo\CoreBundle\Entity\UserFieldValues $attributeValue */
        $attributeValue = $event->getData();
        $form = $event->getForm();

        if (null === $attributeValue) {
            $form->add(
                $this->factory->createNamed(
                    'value',
                    'text',
                    null,
                    array('auto_initialize' => false)
                )
            );

            return;
        }

        $type = $attributeValue->getType();
        $attributeValue->setAttribute($attributeValue->getField());

        $options = array('label' => $attributeValue->getName(), 'auto_initialize' => false);

        if (is_array($attributeValue->getConfiguration())) {
            $options = array_merge($options, $attributeValue->getConfiguration());
        }

        $this->verifyValue($attributeValue);

        // If we're editing the attribute value, let's just render the value field, not full selection.

        $form
            ->remove('extraField')
            ->add($this->factory->createNamed('value', $type, null, $options))
        ;
    }

    /**
     * Verify value before set to form
     *
     * @param AttributeValueInterface $attributeValue
     */
    private function verifyValue(AttributeValueInterface $attributeValue)
    {
        switch ($attributeValue->getType()) {

            case AttributeTypes::CHECKBOX:
                if (!is_bool($attributeValue->getValue())) {
                    $attributeValue->setValue(false);
                }

                break;

            case AttributeTypes::CHOICE:
                if (!is_array($attributeValue->getValue())) {
                    $attributeValue->setValue(null);
                }

                break;

            case AttributeTypes::MONEY:
            case AttributeTypes::NUMBER:
            case AttributeTypes::PERCENTAGE:
                if (!is_numeric($attributeValue->getValue())) {
                    $attributeValue->setValue(null);
                }

                break;
        }
    }
}
