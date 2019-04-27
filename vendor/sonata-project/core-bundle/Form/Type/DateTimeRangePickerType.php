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

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * DateTimeRangePickerType.
 *
 * @author Andrej Hudec <pulzarraider@gmail.com>
 */
class DateTimeRangePickerType extends DateTimeRangeType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'field_options' => array(),
            'field_options_start' => array(),
            'field_options_end' => array(),
            // NEXT_MAJOR: Remove ternary and keep 'Sonata\CoreBundle\Form\Type\DateTimePickerType'
            // (when requirement of Symfony is >= 2.8)
            'field_type' => method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
                ? 'Sonata\CoreBundle\Form\Type\DateTimePickerType'
                : 'sonata_type_datetime_picker',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'sonata_type_datetime_range_picker';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }
}
