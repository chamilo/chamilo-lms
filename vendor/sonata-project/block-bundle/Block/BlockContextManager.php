<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Block;

use Doctrine\Common\Util\ClassUtils;
use Psr\Log\LoggerInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BlockContextManager implements BlockContextManagerInterface
{
    protected $blockLoader;

    protected $blockService;

    protected $settingsByType;

    protected $settingsByClass;

    protected $cacheBlocks;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param BlockLoaderInterface         $blockLoader
     * @param BlockServiceManagerInterface $blockService
     * @param array                        $cacheBlocks
     * @param LoggerInterface|null         $logger
     */
    public function __construct(BlockLoaderInterface $blockLoader, BlockServiceManagerInterface $blockService,
        array $cacheBlocks = array(), LoggerInterface $logger = null
    ) {
        $this->blockLoader  = $blockLoader;
        $this->blockService = $blockService;
        $this->cacheBlocks  = $cacheBlocks;
        $this->logger       = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function addSettingsByType($type, array $settings, $replace = false)
    {
        $typeSettings = isset($this->settingsByType[$type]) ? $this->settingsByType[$type] : array();
        if ($replace) {
            $this->settingsByType[$type] = array_merge($typeSettings, $settings);
        } else {
            $this->settingsByType[$type] = array_merge($settings, $typeSettings);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addSettingsByClass($class, array $settings, $replace = false)
    {
        $classSettings = isset($this->settingsByClass[$class]) ? $this->settingsByClass[$class] : array();
        if ($replace) {
            $this->settingsByClass[$class] = array_merge($classSettings, $settings);
        } else {
            $this->settingsByClass[$class] = array_merge($settings, $classSettings);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($meta, array $settings = array())
    {
        if (!$meta instanceof BlockInterface) {
            $block = $this->blockLoader->load($meta);

            if (is_array($meta) && isset($meta['settings'])) {
                // merge user settings
                $settings = array_merge($meta['settings'], $settings);
            }
        } else {
            $block = $meta;
        }

        if (!$block instanceof BlockInterface) {
            return false;
        }

        $optionsResolver = new OptionsResolver();

        $this->setDefaultSettings($optionsResolver, $block);

        $service = $this->blockService->get($block);
        $service->setDefaultSettings($optionsResolver, $block);

        $originalSettings = $settings;
        try {
            $settings = $optionsResolver->resolve(array_merge($block->getSettings(), $settings));
        } catch (ExceptionInterface $e) {
            if ($this->logger) {
                $this->logger->error(sprintf(
                    '[cms::blockContext] block.id=%s - error while resolving options - %s',
                    $block->getId(),
                    $e->getMessage()
                ));
            }

            $optionsResolver = new OptionsResolver();

            $this->setDefaultSettings($optionsResolver, $block);

            $service = $this->blockService->get($block);
            $service->setDefaultSettings($optionsResolver, $block);

            $settings = $optionsResolver->resolve($settings);
        }

        $blockContext = new BlockContext($block, $settings);

        $this->setDefaultExtraCacheKeys($blockContext, $originalSettings);

        return $blockContext;
    }

    /**
     * @param OptionsResolverInterface $optionsResolver
     * @param BlockInterface           $block
     */
    protected function setDefaultSettings(OptionsResolverInterface $optionsResolver, BlockInterface $block)
    {
        // defaults for all blocks
        $optionsResolver->setDefaults(array(
            'use_cache'        => true,
            'extra_cache_keys' => array(),
            'attr'             => array(),
            'template'         => false,
            'ttl'              => (int) $block->getTtl(),
        ));

        $optionsResolver->addAllowedTypes(array(
            'use_cache'         => array('bool'),
            'extra_cache_keys'  => array('array'),
            'attr'              => array('array'),
            'ttl'               => array('int'),
            'template'          => array('string', 'bool'),
        ));

        // add type and class settings for block
        $class = ClassUtils::getClass($block);
        $settingsByType = isset($this->settingsByType[$block->getType()]) ? $this->settingsByType[$block->getType()] : array();
        $settingsByClass = isset($this->settingsByClass[$class]) ? $this->settingsByClass[$class] : array();
        $optionsResolver->setDefaults(array_merge($settingsByType, $settingsByClass));
    }

    /**
     * Adds context settings, to be able to rebuild a block context, to the
     * extra_cache_keys.
     *
     * @param BlockContextInterface $blockContext
     * @param array                 $settings
     */
    protected function setDefaultExtraCacheKeys(BlockContextInterface $blockContext, array $settings)
    {
        if (!$blockContext->getSetting('use_cache') || $blockContext->getSetting('ttl') <= 0) {
            return;
        }

        $block = $blockContext->getBlock();

        // type by block class
        $class = ClassUtils::getClass($block);
        $cacheServiceId = isset($this->cacheBlocks['by_class'][$class]) ? $this->cacheBlocks['by_class'][$class] : false;

        // type by block service
        if (!$cacheServiceId) {
            $cacheServiceId = isset($this->cacheBlocks['by_type'][$block->getType()]) ? $this->cacheBlocks['by_type'][$block->getType()] : false;
        }

        if (!$cacheServiceId) {
            // no context cache needed
            return;
        }

        // do not add cache settings to extra_cache_keys
        unset($settings['use_cache'], $settings['extra_cache_keys'], $settings['ttl']);

        $extraCacheKeys = $blockContext->getSetting('extra_cache_keys');

        // add context settings to extra_cache_keys
        if (!isset($extraCacheKeys[self::CACHE_KEY])) {
            $extraCacheKeys[self::CACHE_KEY] = $settings;
            $blockContext->setSetting('extra_cache_keys', $extraCacheKeys);
        }
    }
}
