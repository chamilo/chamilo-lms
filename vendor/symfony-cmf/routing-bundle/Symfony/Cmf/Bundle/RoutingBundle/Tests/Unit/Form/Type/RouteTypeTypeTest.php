<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Tests\Unit\Form\Type;

use Symfony\Cmf\Bundle\RoutingBundle\Form\Type\RouteTypeType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RouteTypeTypeTest extends \PHPUnit_Framework_Testcase
{
    public function setUp()
    {
        $this->type = new RouteTypeType;
        $this->ori = $this->getMock(
            'Symfony\Component\OptionsResolver\OptionsResolverInterface');
    }

    public function testSetDefaultOptions()
    {
        $type = new RouteTypeType;
        $optionsResolver = new OptionsResolver();

        $type->setDefaultOptions($optionsResolver);

        $options = $optionsResolver->resolve();

        $this->assertInternalType('array', $options['choices']);
    }

    public function testDefaultsSet()
    {
        $this->type->addRouteType('foobar');
        $this->type->addRouteType('barfoo');

        $this->ori->expects($this->once())
            ->method('setDefaults')
            ->with(array(
                'choices' => array(
                    'foobar' => 'route_type.foobar',
                    'barfoo' => 'route_type.barfoo',
                ),
                'translation_domain' => 'CmfRoutingBundle',
            ));

        $this->type->setDefaultOptions($this->ori);
    }
}
