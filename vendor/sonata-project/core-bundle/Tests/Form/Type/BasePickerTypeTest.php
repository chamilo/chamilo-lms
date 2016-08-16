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
use Sonata\CoreBundle\Form\Type\BasePickerType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;

class BasePickerTest extends BasePickerType
{
    public function getName()
    {
        return 'base_picker_test';
    }

    protected function getDefaultFormat()
    {
        return DateTimeType::HTML5_FORMAT;
    }
}

/**
 * @author Hugo Briand <briand@ekino.com>
 */
class BasePickerTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testFinishView()
    {
        $type = new BasePickerTest(new MomentFormatConverter(), $this->getMock('Symfony\Component\Translation\TranslatorInterface'));

        $view = new FormView();
        $form = new Form($this->getMock('Symfony\Component\Form\FormConfigInterface'));

        $type->finishView($view, $form, array('format' => 'yyyy-MM-dd'));

        $this->assertArrayHasKey('moment_format', $view->vars);
        $this->assertArrayHasKey('dp_options', $view->vars);
        $this->assertArrayHasKey('datepicker_use_button', $view->vars);

        foreach ($view->vars['dp_options'] as $dpKey => $dpValue) {
            $this->assertFalse(strpos($dpKey, '_'));
            $this->assertFalse(strpos($dpKey, 'dp_'));
        }

        $this->assertSame('text', $view->vars['type']);
    }

    public function testLegacyConstructor()
    {
        new BasePickerTest(new MomentFormatConverter());
    }
}
