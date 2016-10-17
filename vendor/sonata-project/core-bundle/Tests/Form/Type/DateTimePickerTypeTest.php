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
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

/**
 * @author Hugo Briand <briand@ekino.com>
 */
class DateTimePickerTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildForm()
    {
        // NEXT_MAJOR: Hack for php 5.3 only, remove it when requirement of PHP is >= 5.4
        $that = $this;

        $formBuilder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')->disableOriginalConstructor()->getMock();
        $formBuilder
            ->expects($this->any())
            ->method('add')
            ->will($this->returnCallback(function ($name, $type = null) use ($that) {
                // NEXT_MAJOR: Remove this "if" (when requirement of Symfony is >= 2.8)
                if (method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')) {
                    if (null !== $type) {
                        $isFQCN = class_exists($type);
                        if (!$isFQCN && method_exists('Symfony\Component\Form\AbstractType', 'getName')) {
                            // 2.8
                            @trigger_error(
                                sprintf(
                                    'Accessing type "%s" by its string name is deprecated since version 2.8 and will be removed in 3.0.'
                                    .' Use the fully-qualified type class name instead.',
                                    $type
                                ),
                                E_USER_DEPRECATED)
                            ;
                        }

                        $that->assertTrue($isFQCN, sprintf('Unable to ensure %s is a FQCN', $type));
                    }
                }
            }));

        $type = new DateTimePickerType(
            $this->getMock('Sonata\CoreBundle\Date\MomentFormatConverter'),
            $this->getMock('Symfony\Component\Translation\TranslatorInterface')
        );

        $type->buildForm($formBuilder, array(
            'dp_use_minutes' => true,
            'dp_use_seconds' => true,
            'dp_minute_stepping' => 1,
            'format' => DateTimeType::DEFAULT_DATE_FORMAT,
            'date_format' => null,
        ));
    }

    public function testGetParent()
    {
        $form = new DateTimePickerType(
            $this->getMock('Sonata\CoreBundle\Date\MomentFormatConverter'),
            $this->getMock('Symfony\Component\Translation\TranslatorInterface')
        );

        // NEXT_MAJOR: Remove this "if" (when requirement of Symfony is >= 2.8)
        if (method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')) {
            $parentRef = $form->getParent();

            $isFQCN = class_exists($parentRef);
            if (!$isFQCN && method_exists('Symfony\Component\Form\AbstractType', 'getName')) {
                // 2.8
                @trigger_error(
                    sprintf(
                        'Accessing type "%s" by its string name is deprecated since version 2.8 and will be removed in 3.0.'
                        .' Use the fully-qualified type class name instead.',
                        $parentRef
                    ),
                    E_USER_DEPRECATED)
                ;
            }

            $this->assertTrue($isFQCN, sprintf('Unable to ensure %s is a FQCN', $parentRef));
        }
    }

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
