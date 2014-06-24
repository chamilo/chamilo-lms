<?php

namespace FOS\MessageBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;

class FOSMessageExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $config = $processor->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        if (!in_array(strtolower($config['db_driver']), array('orm', 'mongodb'))) {
            throw new \InvalidArgumentException(sprintf('Invalid db driver "%s".', $config['db_driver']));
        }
        $loader->load(sprintf('%s.xml', $config['db_driver']));
        $loader->load('config.xml');
        $loader->load('form.xml');
        $loader->load('validator.xml');
        $loader->load('spam_detection.xml');

        $container->setParameter('fos_message.message_class', $config['message_class']);
        $container->setParameter('fos_message.thread_class', $config['thread_class']);

        $container->setParameter('fos_message.new_thread_form.model', $config['new_thread_form']['model']);
        $container->setParameter('fos_message.new_thread_form.name', $config['new_thread_form']['name']);
        $container->setParameter('fos_message.reply_form.model', $config['reply_form']['model']);
        $container->setParameter('fos_message.reply_form.name', $config['reply_form']['name']);

        $container->setAlias('fos_message.message_manager', $config['message_manager']);
        $container->setAlias('fos_message.thread_manager', $config['thread_manager']);

        $container->setAlias('fos_message.sender', $config['sender']);
        $container->setAlias('fos_message.composer', $config['composer']);
        $container->setAlias('fos_message.provider', $config['provider']);
        $container->setAlias('fos_message.participant_provider', $config['participant_provider']);
        $container->setAlias('fos_message.authorizer', $config['authorizer']);
        $container->setAlias('fos_message.message_reader', $config['message_reader']);
        $container->setAlias('fos_message.thread_reader', $config['thread_reader']);
        $container->setAlias('fos_message.deleter', $config['deleter']);
        $container->setAlias('fos_message.spam_detector', $config['spam_detector']);
        $container->setAlias('fos_message.twig_extension', $config['twig_extension']);

        $container->setAlias('fos_message.new_thread_form.type', $config['new_thread_form']['type']);
        $container->setAlias('fos_message.new_thread_form.factory', $config['new_thread_form']['factory']);
        $container->setAlias('fos_message.new_thread_form.handler', $config['new_thread_form']['handler']);
        $container->setAlias('fos_message.reply_form.type', $config['reply_form']['type']);
        $container->setAlias('fos_message.reply_form.factory', $config['reply_form']['factory']);
        $container->setAlias('fos_message.reply_form.handler', $config['reply_form']['handler']);

        $container->setAlias('fos_message.search_query_factory', $config['search']['query_factory']);
        $container->setAlias('fos_message.search_finder', $config['search']['finder']);
        $container->getDefinition('fos_message.search_query_factory.default')
            ->replaceArgument(1, $config['search']['query_parameter']);

        $container->getDefinition('fos_message.recipients_data_transformer')
            ->replaceArgument(0, new Reference($config['user_transformer']));
    }
}
