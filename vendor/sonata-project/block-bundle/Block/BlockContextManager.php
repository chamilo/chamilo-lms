<?php

/*
 * This file is part of the Sonata Project package.
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
    /**
     * @var BlockLoaderInterface
     */
    protected $blockLoader;

    /**
     * @var BlockServiceManagerInterface
     */
    protected $blockService;

    /**
     * @var array
     */
    protected $settingsByType;

    /**
     * @var array
     */
    protected $settingsByClass;

    /**
     * @var array
     */
    protected $cacheBlocks;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Used for deprecation check on {@link resolve} method.
     * To be removed in 4.0 with BC system.
     *
     * @var array
     */
    private $reflectionCache;

    /**
     * @param BlockLoaderInterface         $blockLoader
     * @param BlockServiceManagerInterface $blockService
     * @param array                        $cacheBlocks
     * @param LoggerInterface|null         $logger
     */
    public function __construct(BlockLoaderInterface $blockLoader, BlockServiceManagerInterface $blockService,
        array $cacheBlocks = array(), LoggerInterface $logger = null
    ) {
        $this->blockLoader = $blockLoader;
        $this->blockService = $blockService;
        $this->cacheBlocks = $cacheBlocks;
        $this->logger = $logger;
        $this->reflectionCache = array();
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
     * Check if a given block type exists.
     *
     * @param string $type Block type to check for
     *
     * @return bool
     */
    public function exists($type)
    {
        return $this->blockLoader->exists($type);
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

        $originalSettings = $settings;
        try {
            $settings = $this->resolve($block, array_merge($block->getSettings(), $settings));
        } catch (ExceptionInterface $e) {
            if ($this->logger) {
                $this->logger->error(sprintf(
                    '[cms::blockContext] block.id=%s - error while resolving options - %s',
                    $block->getId(),
                    $e->getMessage()
                ));
            }

            $settings = $this->resolve($block, $settings);
        }

        $blockContext = new BlockContext($block, $settings);

        $this->setDefaultExtraCacheKeys($blockContext, $originalSettings);

        return $blockContext;
    }

    /**
     * NEXT_MAJOR: remove this method.
     *
     * @param OptionsResolverInterface $optionsResolver
     * @param BlockInterface           $block
     *
     * @deprecated since version 2.3, to be renamed in 4.0.
     *             Use the method configureSettings instead
     */
    protected function setDefaultSettings(OptionsResolverInterface $optionsResolver, BlockInterface $block)
    {
        if (get_called_class() !== __CLASS__) {
            @trigger_error(
                'The '.__METHOD__.' is deprecated since version 2.3, to be renamed in 4.0.'
                .' Use '.__CLASS__.'::configureSettings instead.',
                E_USER_DEPRECATED
            );
        }
        $this->configureSettings($optionsResolver, $block);
    }

    protected function configureSettings(OptionsResolver $optionsResolver, BlockInterface $block)
    {
        // defaults for all blocks
        $optionsResolver->setDefaults(array(
            'use_cache' => true,
            'extra_cache_keys' => array(),
            'attr' => array(),
            'template' => false,
            'ttl' => (int) $block->getTtl(),
        ));

        // TODO: Remove it when bumping requirements to SF 2.6+
        if (method_exists($optionsResolver, 'setDefined')) {
            $optionsResolver
                ->addAllowedTypes('use_cache', 'bool')
                ->addAllowedTypes('extra_cache_keys', 'array')
                ->addAllowedTypes('attr', 'array')
                ->addAllowedTypes('ttl', 'int')
                ->addAllowedTypes('template', array('string', 'bool'))
            ;
        } else {
            $optionsResolver->addAllowedTypes(array(
                'use_cache' => array('bool'),
                'extra_cache_keys' => array('array'),
                'attr' => array('array'),
                'ttl' => array('int'),
                'template' => array('string', 'bool'),
            ));
        }

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

    /**
     * @param BlockInterface $block
     * @param array          $settings
     *
     * @return array
     */
    private function resolve(BlockInterface $block, $settings)
    {
        $optionsResolver = new \Sonata\BlockBundle\Util\OptionsResolver();

        $this->configureSettings($optionsResolver, $block);

        $service = $this->blockService->get($block);

        /* use new interface method whenever possible */
        if (method_exists($service, 'configureSettings')) {
            $service->configureSettings($optionsResolver, $block);
        } else {
            $service->setDefaultSettings($optionsResolver, $block);
        }

        // Caching method reflection
        $serviceClass = get_class($service);
        if (!isset($this->reflectionCache[$serviceClass])) {
            $reflector = new \ReflectionMethod($service, 'setDefaultSettings');
            $isOldOverwritten = $reflector->getDeclaringClass()->getName() !== 'Sonata\BlockBundle\Block\AbstractBlockService';

            // Prevention for service classes implementing directly the interface and not extends the new base class
            if (!method_exists($service, 'configureSettings')) {
                $isNewOverwritten = false;
            } else {
                $reflector = new \ReflectionMethod($service, 'configureSettings');
                $isNewOverwritten = $reflector->getDeclaringClass()->getName() !== 'Sonata\BlockBundle\Block\AbstractBlockService';
            }

            $this->reflectionCache[$serviceClass] = array(
                'isOldOverwritten' => $isOldOverwritten,
                'isNewOverwritten' => $isNewOverwritten,
            );
        }

        if ($this->reflectionCache[$serviceClass]['isOldOverwritten'] && !$this->reflectionCache[$serviceClass]['isNewOverwritten']) {
            @trigger_error(
                'The Sonata\BlockBundle\Block\BlockServiceInterface::setDefaultSettings() method is deprecated'
                .' since version 2.3 and will be removed in 4.0. Use configureSettings() instead.'
                .' This method will be added to the BlockServiceInterface with SonataBlockBundle 4.0.',
                E_USER_DEPRECATED
            );
        }

        return $optionsResolver->resolve($settings);
    }
}
