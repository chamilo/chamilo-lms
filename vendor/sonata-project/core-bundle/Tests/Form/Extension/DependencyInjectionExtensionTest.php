<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\Form\Extension;

use Sonata\CoreBundle\Form\Extension\DependencyInjectionExtension;
use Symfony\Component\HttpKernel\Kernel;

class DependencyInjectionExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testValidType()
    {
        $type = $this->getMock('Symfony\Component\Form\FormTypeInterface');

        if (Kernel::MAJOR_VERSION < 3) {
            $type->expects($this->any())
                ->method('getName')
                ->will($this->returnValue('Symfony\Component\Form\Type\FormType'));
        }

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->any())->method('has')->will($this->returnValue(true));
        $container->expects($this->any())
            ->method('get')
            ->with($this->equalTo('symfony.form.type.form'))
            ->will($this->returnValue($type));

        $typeServiceIds = array(
            'Symfony\Component\Form\Type\FormType' => 'symfony.form.type.form',
        );

        $typeExtensionServiceIds = array();
        $guesserServiceIds       = array();
        $mappingTypes            = array(
            'form' => 'Symfony\Component\Form\Type\FormType',
        );
        $extensionTypes          = array();

        $f = new DependencyInjectionExtension($container, $typeServiceIds, $typeExtensionServiceIds, $guesserServiceIds, $mappingTypes, $extensionTypes);

        $f->getType('form');
        $f->getType('Symfony\Component\Form\Type\FormType');
    }

    public function testTypeExtensionsValid()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->any())->method('has')->will($this->returnValue(true));
        $container->expects($this->any())
            ->method('get')
            ->withConsecutive(
                array($this->equalTo('symfony.form.type.form_extension')),
                array($this->equalTo('sonata.form.type.form_extension'))
            )
        ;

        $typeServiceIds = array();
        $typeExtensionServiceIds = array(
            'Symfony\Component\Form\Type\FormType' => array(
                'symfony.form.type.form_extension',
            ),
        );
        $guesserServiceIds       = array();
        $mappingTypes            = array(
            'form' => 'Symfony\Component\Form\Type\FormType',
        );
        $extensionTypes          = array(
            'form' => array(
                'sonata.form.type.form_extension',
            ),
        );

        $f = new DependencyInjectionExtension($container, $typeServiceIds, $typeExtensionServiceIds, $guesserServiceIds, $mappingTypes, $extensionTypes);

        $this->assertCount(2, $f->getTypeExtensions('Symfony\Component\Form\Type\FormType'));
    }
}
