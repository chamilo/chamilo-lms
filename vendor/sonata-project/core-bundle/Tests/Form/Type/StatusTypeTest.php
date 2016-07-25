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
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Choice
{
    public static $list;

    public static function getList()
    {
        return static::$list;
    }
}

class StatusType extends \Sonata\CoreBundle\Form\Type\BaseStatusType
{
}

class StatusTypeTest extends TypeTestCase
{
    public function testGetDefaultOptions()
    {
        Choice::$list = array(
            1 => 'salut',
        );

        $type = new StatusType('Sonata\CoreBundle\Tests\Form\Type\Choice', 'getList', 'choice_type');

        $this->assertSame('choice_type', $type->getName());
        $this->assertSame('choice', $type->getParent());

        FormHelper::configureOptions($type, $resolver = new OptionsResolver());

        $options = $resolver->resolve(array());

        $this->assertArrayHasKey('choices', $options);
        $this->assertSame($options['choices'], array(1 => 'salut'));
    }

    public function testGetDefaultOptionsWithValidFlip()
    {
        Choice::$list = array(
            1 => 'salut',
            2 => 'toi!',
        );

        $type = new StatusType('Sonata\CoreBundle\Tests\Form\Type\Choice', 'getList', 'choice_type', true);

        $this->assertSame('choice_type', $type->getName());
        $this->assertSame('choice', $type->getParent());

        FormHelper::configureOptions($type, $resolver = new OptionsResolver());

        $options = $resolver->resolve(array());

        $this->assertArrayHasKey('choices', $options);
        $this->assertSame($options['choices'], array('salut' => 1, 'toi!' => 2));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetDefaultOptionsWithValidInvalidFlip()
    {
        Choice::$list = array(
            1 => 'error',
            2 => 'error',
        );

        $type = new StatusType('Sonata\CoreBundle\Tests\Form\Type\Choice', 'getList', 'choice_type', true);

        $this->assertSame('choice_type', $type->getName());
        $this->assertSame('choice', $type->getParent());

        FormHelper::configureOptions($type, $resolver = new OptionsResolver());

        $options = $resolver->resolve(array());
    }
}
