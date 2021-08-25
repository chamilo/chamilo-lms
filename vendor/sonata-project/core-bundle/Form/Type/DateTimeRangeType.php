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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DateTimeRangeType extends AbstractType
{
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
     * @deprecated translator dependency is deprecated since version 3.1, to be removed in 4.0
     */
    public function __construct(TranslatorInterface $translator = null)
    {
        // check if class is overloaded and notify about removing deprecated translator
        if ($translator !== null && get_class($this) !== get_class() && get_class($this) !== 'Sonata\CoreBundle\Form\Type\DateTimeRangePickerType') {
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
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options['field_options_start'] = array_merge(
            array(
                'label' => 'date_range_start',
                'translation_domain' => 'SonataCoreBundle',
            ),
            $options['field_options_start']
        );

        $options['field_options_end'] = array_merge(
            array(
                'label' => 'date_range_end',
                'translation_domain' => 'SonataCoreBundle',
            ),
            $options['field_options_end']
        );

        $builder->add('start', $options['field_type'], array_merge(array('required' => false), $options['field_options'], $options['field_options_start']));
        $builder->add('end', $options['field_type'], array_merge(array('required' => false), $options['field_options'], $options['field_options_end']));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'sonata_type_datetime_range';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
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
        $resolver->setDefaults(array(
            'field_options' => array(),
            'field_options_start' => array(),
            'field_options_end' => array(),
            // NEXT_MAJOR: Remove ternary and keep 'Symfony\Component\Form\Extension\Core\Type\DateTimeType'
            // (when requirement of Symfony is >= 2.8)
            'field_type' => method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
                ? 'Symfony\Component\Form\Extension\Core\Type\DateTimeType'
                : 'datetime',
        ));
    }
}
