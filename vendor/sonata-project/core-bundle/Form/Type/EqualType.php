<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

class EqualType extends AbstractType
{
    const TYPE_IS_EQUAL = 1;

    const TYPE_IS_NOT_EQUAL = 2;

    /**
     * NEXT_MAJOR: remove this property.
     *
     * @var TranslatorInterface|null
     *
     * @deprecated translator property is deprecated since version 3.1, to be removed in 4.0
     */
    protected $translator;

    /**
     * NEXT_MAJOR: remove this method.
     *
     * @param TranslatorInterface|null $translator
     *
     * @deprecated translator property is deprecated since version 3.1, to be removed in 4.0
     */
    public function __construct(TranslatorInterface $translator = null)
    {
        // check if class is overloaded and notify about removing deprecated translator
        if ($translator !== null && get_class($this) !== get_class()) {
            @trigger_error(
                'The translator dependency in '.__CLASS__.' is deprecated since 3.1 and will be removed in 4.0. '.
                'Please prepare your dependencies for this change.',
                E_USER_DEPRECATED
            );
        }

        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     *
     * @todo Remove it when bumping requirements to SF 2.7+
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = array(
            self::TYPE_IS_EQUAL => 'label_type_equals',
            self::TYPE_IS_NOT_EQUAL => 'label_type_not_equals',
        );

        $defaultOptions = array(
            'choice_translation_domain' => 'SonataCoreBundle',
        );

        // SF 2.7+ BC
        if (method_exists('Symfony\Component\Form\AbstractType', 'configureOptions')) {
            $choices = array_flip($choices);

            // choice_as_value options is not needed in SF 3.0+
            if (method_exists('Symfony\Component\Form\FormTypeInterface', 'setDefaultOptions')) {
                $defaultOptions['choices_as_values'] = true;
            }
        }

        $defaultOptions['choices'] = $choices;

        $resolver->setDefaults($defaultOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix') ?
            'Symfony\Component\Form\Extension\Core\Type\ChoiceType' :
            'choice' // SF <2.8 BC
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'sonata_type_equal';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }
}
