<?php

/**
 * This file is part of the FakerBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Bazinga\Bundle\FakerBundle\Tests\DependencyInjection;

use Bazinga\Bundle\FakerBundle\Tests\TestCase;
use Bazinga\Bundle\FakerBundle\DependencyInjection\BazingaFakerExtension;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class BazingaFakerExtensionTest extends TestCase
{
    public function getContainer()
    {
        return new ContainerBuilder(new ParameterBag(array(
            'kernel.root_dir' => __DIR__.'/../../',
        )));
    }

    public function testLoadWithCustomPopulator()
    {
        $container = $this->getContainer();
        $loader    = new BazingaFakerExtension();

        $loader->load(array(array('populator' => '\Foo\Bar')), $container);

        $this->assertEquals('\Foo\Bar', $container->getParameter('faker.populator.class'));
        try {
            $container->get('faker.populator');
            $this->fail('\Foo\Bar doesn\'t exist so it should throw an exception');
        } catch (\ReflectionException $e) {
            $this->assertEquals('Class \Foo\Bar does not exist', $e->getMessage(), 'Check that the loaded populator is well configured');
        }
    }
}
