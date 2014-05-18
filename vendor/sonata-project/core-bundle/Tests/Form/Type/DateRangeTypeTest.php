<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\Form\Type;

use Sonata\CoreBundle\Form\Type\DateRangeType;

use Symfony\Component\Form\Tests\Extension\Core\Type\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateRangeTypeTest extends TypeTestCase
{
    public function testGetDefaultOptions()
    {
        $type = new DateRangeType($this->getMock('Symfony\Component\Translation\TranslatorInterface'));

        $this->assertEquals('sonata_type_date_range', $type->getName());

        $resolver = new OptionsResolver();
        $type->setDefaultOptions($resolver);

        $options = $resolver->resolve();

        $this->assertEquals(array('field_options' => array()), $options);
    }
}
