<?php

/*
 * This file is part of the FOSAdvancedEncoderBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\AdvancedEncoderBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class FOSAdvancedEncoderExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('encoder.xml');

        $encoders = array();
        foreach ($config['encoders'] as $name => $encoder) {
            $encoders[$name] = $this->createEncoder($encoder);
        }

        $container->getDefinition('fos_advanced_encoder.encoder_factory')->replaceArgument(1, $encoders);
    }

    private function createEncoder($config)
    {
        // a custom encoder service
        if (isset($config['id'])) {
            return new Reference($config['id']);
        }

        // plaintext encoder
        if ('plaintext' === $config['algorithm']) {
            $arguments = array($config['ignore_case']);

            return array(
                'class' => new Parameter('security.encoder.plain.class'),
                'arguments' => $arguments,
            );
        }

        // bcrypt encoder
        if ('bcrypt' === $config['algorithm']) {
            $arguments = array($config['cost']);

            return array(
                'class' => new Parameter('security.encoder.bcrypt.class'),
                'arguments' => $arguments,
            );
        }

        // pbkdf2 encoder
        if ('pbkdf2' === $config['algorithm']) {
            $arguments = array(
                $config['hash_algorithm'],
                $config['encode_as_base64'],
                $config['iterations'],
                $config['key_length'],
            );

            return array(
                'class'     => new Parameter('security.encoder.pbkdf2.class'),
                'arguments' => $arguments,
            );
        }
        
        // message digest encoder
        $arguments = array(
            $config['algorithm'],
            $config['encode_as_base64'],
            $config['iterations'],
        );

        return array(
            'class' => new Parameter('security.encoder.digest.class'),
            'arguments' => $arguments,
        );
    }
}
