<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Sonata\CoreBundle\DependencyInjection\SonataCoreExtension;

class SonataCoreExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return array(
            new SonataCoreExtension(),
        );
    }

    public function testAfterLoadingTheWrappingParameterIsSet()
    {
        $this->load();
        $this->assertContainerBuilderHasParameter(
            'sonata.core.form_type'
        );
        $this->assertEquals(
            'standard',
            $this->container->getParameter(
                'sonata.core.form_type'
            )
        );
    }

    public function testHorizontalFormTypeMeansNoWrapping()
    {
        $this->load(array('form_type' => 'horizontal'));
        $this->assertContainerBuilderHasParameter(
            'sonata.core.form_type'
        );
        $this->assertEquals(
            'horizontal',
            $this->container->getParameter(
                'sonata.core.form_type'
            )
        );
    }
}
