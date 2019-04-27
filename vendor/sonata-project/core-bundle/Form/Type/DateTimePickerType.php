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

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * @author Hugo Briand <briand@ekino.com>
 */
class DateTimePickerType extends BasePickerType
{
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
        $resolver->setDefaults(array_merge($this->getCommonDefaults(), array(
            'dp_use_minutes' => true,
            'dp_use_seconds' => true,
            'dp_minute_stepping' => 1,
            'format' => DateTimeType::DEFAULT_DATE_FORMAT,
            'date_format' => null,
        )));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix') ?
            'Symfony\Component\Form\Extension\Core\Type\DateTimeType' :
            'datetime' // SF <2.8 BC
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'sonata_type_datetime_picker';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }
}
