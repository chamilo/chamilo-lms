<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Sonata\CoreBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


/**
 * Class DatePickerType
 *
 * @package Sonata\CoreBundle\Form\Type
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class DateTimePickerType extends BasePickerType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array_merge($this->getCommonDefaults(), array(
            'dp_use_minutes'     => true,
            'dp_use_seconds'     => true,
            'dp_minute_stepping' => 1,
        )));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'datetime';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sonata_type_datetime_picker';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultFormat()
    {
        return DateTimeType::HTML5_FORMAT;
    }
}