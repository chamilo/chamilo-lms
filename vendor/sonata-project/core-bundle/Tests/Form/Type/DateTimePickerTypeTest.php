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

use Sonata\CoreBundle\Date\MomentFormatConverter;
use Sonata\CoreBundle\Form\Type\DateTimePickerType;

/**
 * Class DatePickerTypeTest.
 *
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class DateTimePickerTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testGetName()
    {
        $type = new DateTimePickerType(new MomentFormatConverter(), $this->getMock('Symfony\Component\Translation\TranslatorInterface'));

        $this->assertSame('sonata_type_datetime_picker', $type->getName());
    }

    public function testLegacyConstructor()
    {
        $type = new DateTimePickerType(new MomentFormatConverter());

        $this->assertSame('sonata_type_datetime_picker', $type->getName());
    }
}
