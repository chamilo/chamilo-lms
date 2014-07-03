<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\Twig\Extension;

use Sonata\CoreBundle\Twig\Extension\StatusExtension;

class StatusExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetName()
    {
        $extension = new StatusExtension();
        $this->assertEquals('sonata_core_status', $extension->getName());
    }

    public function testGetFilters()
    {
        $extension = new StatusExtension();
        $filters = $extension->getFilters();

        $this->assertTrue(isset($filters['sonata_status_class']));
        $this->assertInstanceOf('Twig_Filter_Method', $filters['sonata_status_class']);
    }

    public function testStatusClassDefaultValue()
    {
        $extension = new StatusExtension();
        $statusService = $this->getMockBuilder('Sonata\CoreBundle\Component\Status\StatusClassRendererInterface')
            ->getMock();
        $statusService->expects($this->once())
            ->method('handlesObject')
            ->will($this->returnValue(false));

        $extension->addStatusService($statusService);
        $this->assertEquals('test-value', $extension->statusClass(new \stdClass(), 'getStatus', 'test-value'));
    }
}