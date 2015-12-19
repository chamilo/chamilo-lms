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

use Sonata\CoreBundle\Form\FormHelper;
use Sonata\CoreBundle\Form\Type\TranslatableChoiceType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TranslatableChoiceTypeTest extends TypeTestCase
{
    public function testLegacyGetDefaultOptions()
    {
        $stub = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $type = new TranslatableChoiceType($stub);

        FormHelper::configureOptions($type, $resolver = new OptionsResolver());

        $this->assertEquals('choice', $type->getParent());

        $options = $resolver->resolve(array('catalogue' => 'foo'));

        $this->assertEquals('foo', $options['catalogue']);
    }
}
