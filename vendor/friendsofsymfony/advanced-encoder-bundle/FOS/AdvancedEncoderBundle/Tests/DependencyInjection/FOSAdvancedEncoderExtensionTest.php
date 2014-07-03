<?php

/*
 * This file is part of the FOSAdvancedEncoderBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\AdvancedEncoderBundle\Tests\DependencyInjection;

use FOS\AdvancedEncoderBundle\DependencyInjection\FOSAdvancedEncoderExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

class FOSAdvancedEncoderExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadEmptyConfiguration()
    {
        $container = new ContainerBuilder();
        $loader = new FOSAdvancedEncoderExtension();
        $loader->load(array(array()), $container);
        $this->assertTrue($container->hasDefinition('fos_advanced_encoder.encoder_factory'));
    }

    public function testLoadFullConfiguration()
    {
        $container = new ContainerBuilder();
        $loader = new FOSAdvancedEncoderExtension();
        $config = array(
            'encoders' => array(
                'sha1' => 'sha1',
                'md5' => array(
                    'algorithm' => 'md5',
                    'iterations' => 10,
                    'encode_as_base64' => false,
                ),
                'plaintext' => 'plaintext',
                'custom' => array(
                    'id' => 'acme_demo.encoder',
                ),
                'bcrypt' => array(
                    'algorithm' => 'bcrypt',
                    'cost'      => 16,
                ),
                'pbkdf2' => array(
                    'algorithm' => 'pbkdf2',
                    'hash_algorithm' => 'sha512',
                    'encode_as_base64' => false,
                    'iterations' => 2,
                    'key_length' => 40,
                ),
            ),
        );
        $loader->load(array($config), $container);
        $this->assertTrue($container->hasDefinition('fos_advanced_encoder.encoder_factory'));
        $this->assertDefinitionConstructorArguments(
            $container->getDefinition('fos_advanced_encoder.encoder_factory'),
            array(
                new Reference('fos_advanced_encoder.encoder_factory.parent'),
                array(
                    'sha1' => array(
                        'class' => new Parameter('security.encoder.digest.class'),
                        'arguments' => array('sha1', true, 5000),
                    ),
                    'md5' => array(
                        'class' => new Parameter('security.encoder.digest.class'),
                        'arguments' => array('md5', false, 10),
                    ),
                    'plaintext' => array(
                        'class' => new Parameter('security.encoder.plain.class'),
                        'arguments' => array(false),
                    ),
                    'custom' => new Reference('acme_demo.encoder'),
                    'bcrypt' => array(
                        'class' => new Parameter('security.encoder.bcrypt.class'),
                        'arguments' => array(16),
                    ),
                    'pbkdf2' => array(
                        'class' => new Parameter('security.encoder.pbkdf2.class'),
                        'arguments' => array('sha512', false, 2, 40),
                    ),
                )
            )
        );
    }

    private function assertDefinitionConstructorArguments(Definition $definition, array $args)
    {
        $this->assertEquals($args, $definition->getArguments(), "Expected and actual DIC Service constructor arguments of definition '".$definition->getClass()."' don't match.");
    }
}
