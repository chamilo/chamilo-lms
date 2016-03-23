<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Tests\Form\Type;

use Sonata\BlockBundle\Form\Type\ServiceListType;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceListTypeTest extends \PHPUnit_Framework_TestCase
{

    public function testFormType()
    {
        $blockServiceManager = $this->getMock('Sonata\BlockBundle\Block\BlockServiceManagerInterface');

        $type = new ServiceListType($blockServiceManager);

        $this->assertEquals('sonata_block_service_choice', $type->getName());
        $this->assertEquals('choice', $type->getParent());
    }

    /**
     * @expectedException Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     */
    public function testOptionsWithInvalidContext()
    {

        $blockServiceManager = $this->getMock('Sonata\BlockBundle\Block\BlockServiceManagerInterface');

        $type = new ServiceListType($blockServiceManager);

        $resolver = new OptionsResolver();

        $type->setDefaultOptions($resolver);

        $resolver->resolve();
    }

    public function testOptionWithValidContext()
    {
        $blockService = $this->getMock('Sonata\BlockBundle\Block\BlockServiceInterface');
        $blockService->expects($this->once())->method('getName')->will($this->returnValue('value'));

        $blockServiceManager = $this->getMock('Sonata\BlockBundle\Block\BlockServiceManagerInterface');
        $blockServiceManager
            ->expects($this->once())
            ->method('getServicesByContext')
            ->with($this->equalTo('cms'))
            ->will($this->returnValue(array('my.service.code' => $blockService)));

        $type = new ServiceListType($blockServiceManager, array(
            'cms' => array('my.service.code')
        ));

        $resolver = new OptionsResolver();

        $type->setDefaultOptions($resolver);

        $options = $resolver->resolve(array(
            'context'            => 'cms',
        ));

        $expected = array(
            'multiple'  => false,
            'expanded'  => false,
            'choices'   => array (
                'my.service.code' => 'value - my.service.code',
            ),
            'preferred_choices' => array (),
            'empty_data'        => '',
            'empty_value'       => NULL,
            'error_bubbling'    => false,
            'context'           => 'cms',
            'include_containers' => false
        );

        $this->assertEquals($expected, $options);
    }
}