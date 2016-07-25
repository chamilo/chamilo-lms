<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\Form\Type;

use Sonata\CoreBundle\Form\FormHelper;
use Sonata\CoreBundle\Form\Type\DateRangePickerType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateRangePickerTypeTest extends TypeTestCase
{
    public function testGetDefaultOptions()
    {
        $type = new DateRangePickerType($this->getMock('Symfony\Component\Translation\TranslatorInterface'));

        $this->assertSame('sonata_type_date_range_picker', $type->getName());

        FormHelper::configureOptions($type, $resolver = new OptionsResolver());

        $options = $resolver->resolve();

        $this->assertSame(
            array(
                'field_options'       => array(),
                'field_options_start' => array(),
                'field_options_end'   => array(),
                'field_type'          => 'sonata_type_date_picker',
            ), $options);
    }
}
