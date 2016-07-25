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
use Sonata\CoreBundle\Form\Type\CollectionType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CollectionTypeTest extends TypeTestCase
{
    public function testGetDefaultOptions()
    {
        $type = new CollectionType();

        FormHelper::configureOptions($type, $optionResolver = new OptionsResolver());

        $options = $optionResolver->resolve();

        $this->assertFalse($options['modifiable']);
        $this->assertSame('text', $options['type']);
        $this->assertSame(0, count($options['type_options']));
        $this->assertSame('link_add', $options['btn_add']);
        $this->assertSame('SonataCoreBundle', $options['btn_catalogue']);
        $this->assertNull($options['pre_bind_data_callback']);
    }
}
