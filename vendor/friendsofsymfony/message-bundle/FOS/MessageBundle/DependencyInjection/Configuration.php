<?php

namespace FOS\MessageBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This class defines the configuration information for the bundle
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree.
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('fos_message');

        $rootNode
            ->children()
                ->scalarNode('db_driver')->cannotBeOverwritten()->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('thread_class')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('message_class')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('message_manager')->defaultValue('fos_message.message_manager.default')->cannotBeEmpty()->end()
                ->scalarNode('thread_manager')->defaultValue('fos_message.thread_manager.default')->cannotBeEmpty()->end()
                ->scalarNode('sender')->defaultValue('fos_message.sender.default')->cannotBeEmpty()->end()
                ->scalarNode('composer')->defaultValue('fos_message.composer.default')->cannotBeEmpty()->end()
                ->scalarNode('provider')->defaultValue('fos_message.provider.default')->cannotBeEmpty()->end()
                ->scalarNode('participant_provider')->defaultValue('fos_message.participant_provider.default')->cannotBeEmpty()->end()
                ->scalarNode('authorizer')->defaultValue('fos_message.authorizer.default')->cannotBeEmpty()->end()
                ->scalarNode('message_reader')->defaultValue('fos_message.message_reader.default')->cannotBeEmpty()->end()
                ->scalarNode('thread_reader')->defaultValue('fos_message.thread_reader.default')->cannotBeEmpty()->end()
                ->scalarNode('deleter')->defaultValue('fos_message.deleter.default')->cannotBeEmpty()->end()
                ->scalarNode('spam_detector')->defaultValue('fos_message.noop_spam_detector')->cannotBeEmpty()->end()
                ->scalarNode('twig_extension')->defaultValue('fos_message.twig_extension.default')->cannotBeEmpty()->end()
                ->scalarNode('user_transformer')->defaultValue('fos_user.user_to_username_transformer')->cannotBeEmpty()->end()
                ->arrayNode('search')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('query_factory')->defaultValue('fos_message.search_query_factory.default')->cannotBeEmpty()->end()
                        ->scalarNode('finder')->defaultValue('fos_message.search_finder.default')->cannotBeEmpty()->end()
                        ->scalarNode('query_parameter')->defaultValue('q')->cannotBeEmpty()->end()
                    ->end()
                ->end()
                ->arrayNode('new_thread_form')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('factory')->defaultValue('fos_message.new_thread_form.factory.default')->cannotBeEmpty()->end()
                        ->scalarNode('type')->defaultValue('fos_message.new_thread_form.type.default')->cannotBeEmpty()->end()
                        ->scalarNode('handler')->defaultValue('fos_message.new_thread_form.handler.default')->cannotBeEmpty()->end()
                        ->scalarNode('name')->defaultValue('message')->cannotBeEmpty()->end()
                        ->scalarNode('model')->defaultValue('FOS\MessageBundle\FormModel\NewThreadMessage')->end()
                    ->end()
                ->end()
                ->arrayNode('reply_form')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('factory')->defaultValue('fos_message.reply_form.factory.default')->cannotBeEmpty()->end()
                        ->scalarNode('type')->defaultValue('fos_message.reply_form.type.default')->cannotBeEmpty()->end()
                        ->scalarNode('handler')->defaultValue('fos_message.reply_form.handler.default')->cannotBeEmpty()->end()
                        ->scalarNode('name')->defaultValue('message')->cannotBeEmpty()->end()
                        ->scalarNode('model')->defaultValue('FOS\MessageBundle\FormModel\ReplyMessage')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
