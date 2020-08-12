<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Templating\Helper;

use Doctrine\Common\Util\ClassUtils;
use Psr\Cache\CacheItemPoolInterface;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\BlockContextManagerInterface;
use Sonata\BlockBundle\Block\BlockRendererInterface;
use Sonata\BlockBundle\Block\BlockServiceInterface;
use Sonata\BlockBundle\Block\BlockServiceManagerInterface;
use Sonata\BlockBundle\Cache\HttpCacheHandlerInterface;
use Sonata\BlockBundle\Event\BlockEvent;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\BlockBundle\Util\RecursiveBlockIterator;
use Sonata\Cache\CacheAdapterInterface;
use Sonata\Cache\CacheManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Templating\Helper\Helper;

class BlockHelper extends Helper
{
    /**
     * @var BlockServiceManagerInterface
     */
    private $blockServiceManager;

    /**
     * @var CacheManagerInterface|null
     */
    private $cacheManager;

    /**
     * @var CacheItemPoolInterface|null
     */
    private $cachePool;

    /**
     * @var array<string, mixed>
     */
    private $cacheBlocks;

    /**
     * @var BlockRendererInterface
     */
    private $blockRenderer;

    /**
     * @var BlockContextManagerInterface
     */
    private $blockContextManager;

    /**
     * @var HttpCacheHandlerInterface|null
     */
    private $cacheHandler;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * This property is a state variable holdings all assets used by the block for the current PHP request
     * It is used to correctly render the javascripts and stylesheets tags on the main layout.
     *
     * @var array
     */
    private $assets;

    /**
     * @var array
     */
    private $traces;

    /**
     * @var Stopwatch|null
     */
    private $stopwatch;

    /**
     * @param CacheManagerInterface|CacheItemPoolInterface|null $cacheManagerOrCachePool
     * @param array<string, mixed>                              $cacheBlocks
     */
    public function __construct(
        BlockServiceManagerInterface $blockServiceManager,
        array $cacheBlocks,
        BlockRendererInterface $blockRenderer,
        BlockContextManagerInterface $blockContextManager,
        EventDispatcherInterface $eventDispatcher,
        $cacheManagerOrCachePool = null,
        HttpCacheHandlerInterface $cacheHandler = null,
        Stopwatch $stopwatch = null
    ) {
        $this->blockServiceManager = $blockServiceManager;
        $this->cacheBlocks = $cacheBlocks;
        $this->blockRenderer = $blockRenderer;
        $this->eventDispatcher = $eventDispatcher;

        if ($cacheManagerOrCachePool instanceof CacheManagerInterface) {
            @trigger_error(
                sprintf(
                    'Passing %s as argument 6 to %s::%s() is deprecated since sonata-project/block-bundle 3.18 and will throw a \TypeError as of 4.0. You must pass an instance of %s instead.',
                    CacheManagerInterface::class,
                    static::class,
                    __FUNCTION__,
                    CacheItemPoolInterface::class
                ),
                E_USER_DEPRECATED
            );

            $this->cacheManager = $cacheManagerOrCachePool;
        } elseif ($cacheManagerOrCachePool instanceof CacheItemPoolInterface) {
            $this->cachePool = $cacheManagerOrCachePool;
        }

        $this->blockContextManager = $blockContextManager;
        $this->cacheHandler = $cacheHandler;
        $this->stopwatch = $stopwatch;

        $this->assets = [
            'js' => [],
            'css' => [],
        ];

        $this->traces = [
            '_events' => [],
        ];
    }

    public function getName()
    {
        return 'sonata_block';
    }

    /**
     * @param string $media    Unused, only kept to not break existing code
     * @param string $basePath Base path to prepend to the stylesheet urls
     *
     * @return array|string
     */
    public function includeJavascripts($media, $basePath = '')
    {
        $html = '';
        foreach ($this->assets['js'] as $javascript) {
            $html .= "\n".sprintf('<script src="%s%s" type="text/javascript"></script>', $basePath, $javascript);
        }

        return $html;
    }

    /**
     * @param string $media    The css media type to use: all|screen|...
     * @param string $basePath Base path to prepend to the stylesheet urls
     *
     * @return array|string
     */
    public function includeStylesheets($media, $basePath = '')
    {
        if (0 === \count($this->assets['css'])) {
            return '';
        }

        $html = sprintf("<style type='text/css' media='%s'>", $media);

        foreach ($this->assets['css'] as $stylesheet) {
            $html .= "\n".sprintf('@import url(%s%s);', $basePath, $stylesheet);
        }

        $html .= "\n</style>";

        return $html;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function renderEvent($name, array $options = [])
    {
        $eventName = sprintf('sonata.block.event.%s', $name);

        // NEXT_MAJOR: remove this when dropping support for symfony/event-dispatcher 3.x
        $reflectionMethod = new \ReflectionMethod($this->eventDispatcher, 'dispatch');
        $param2 = $reflectionMethod->getParameters()[1] ?? null;

        /* @var BlockEvent $event */
        if (null === $param2 || !$param2->hasType() || $param2->getType()->isBuiltin()) {
            $event = $this->eventDispatcher->dispatch(new BlockEvent($options), $eventName);
        } else {
            $event = $this->eventDispatcher->dispatch($eventName, new BlockEvent($options));
        }

        $content = '';

        foreach ($event->getBlocks() as $block) {
            $content .= $this->render($block);
        }

        if ($this->stopwatch) {
            $this->traces['_events'][uniqid('', true)] = [
                'template_code' => $name,
                'event_name' => $eventName,
                'blocks' => $this->getEventBlocks($event),
                'listeners' => $this->getEventListeners($eventName),
            ];
        }

        return $content;
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
        return $this->blockContextManager->exists($type);
    }

    /**
     * @param BlockInterface|array $block
     *
     * @return string|null
     */
    public function render($block, array $options = [])
    {
        $blockContext = $this->blockContextManager->get($block, $options);

        if (!$blockContext instanceof BlockContextInterface) {
            return '';
        }

        $stats = [];

        if ($this->stopwatch) {
            $stats = $this->startTracing($blockContext->getBlock());
        }

        $service = $this->blockServiceManager->get($blockContext->getBlock());

        $this->computeAssets($blockContext, $stats);

        $useCache = $blockContext->getSetting('use_cache');

        $response = null;

        if ($useCache) {
            $response = $this->getCachedBlock(
                $blockContext,
                $service,
                $stats
            );
        }

        if (!$response) {
            $recorder = null;
            if ($this->cacheManager) {
                $recorder = $this->cacheManager->getRecorder();

                if ($recorder) {
                    $recorder->add($blockContext->getBlock());
                    $recorder->push();
                }
            }

            $response = $this->blockRenderer->render($blockContext);
            $contextualKeys = $recorder ? $recorder->pop() : [];

            if ($this->stopwatch) {
                $stats['cache']['contextual_keys'] = $contextualKeys;
            }

            if ($useCache) {
                $this->saveCache($blockContext, $service, $response, $contextualKeys);
            }
        }

        if ($this->stopwatch) {
            // avoid \DateTime because of serialize/unserialize issue in PHP7.3 (https://bugs.php.net/bug.php?id=77302)
            $stats['cache']['created_at'] = null === $response->getDate() ? null : $response->getDate()->getTimestamp();
            $stats['cache']['ttl'] = $response->getTtl() ?: 0;
            $stats['cache']['age'] = $response->getAge();
        }

        // update final ttl for the whole Response
        if ($this->cacheHandler) {
            $this->cacheHandler->updateMetadata($response, $blockContext);
        }

        if ($this->stopwatch) {
            $this->stopTracing($blockContext->getBlock(), $stats);
        }

        return $response->getContent();
    }

    /**
     * Returns the rendering traces.
     *
     * @return array
     */
    public function getTraces()
    {
        return $this->traces;
    }

    /**
     * Traverse the parent block and its children to retrieve the correct list css and javascript only for main block.
     */
    protected function computeAssets(BlockContextInterface $blockContext, array &$stats = null)
    {
        if ($blockContext->getBlock()->hasParent()) {
            return;
        }

        $service = $this->blockServiceManager->get($blockContext->getBlock());

        $assets = [
            'js' => $service->getJavascripts('all'),
            'css' => $service->getStylesheets('all'),
        ];

        if (\count($assets['js']) > 0) {
            @trigger_error(
                'Defining javascripts assets inside a block is deprecated since 3.3.0 and will be removed in 4.0',
                E_USER_DEPRECATED
            );
        }

        if (\count($assets['css']) > 0) {
            @trigger_error(
                'Defining css assets inside a block is deprecated since 3.2.0 and will be removed in 4.0',
                E_USER_DEPRECATED
            );
        }

        if ($blockContext->getBlock()->hasChildren()) {
            $iterator = new \RecursiveIteratorIterator(new RecursiveBlockIterator($blockContext->getBlock()->getChildren()));

            foreach ($iterator as $block) {
                $assets = [
                    'js' => array_merge($this->blockServiceManager->get($block)->getJavascripts('all'), $assets['js']),
                    'css' => array_merge($this->blockServiceManager->get($block)->getStylesheets('all'), $assets['css']),
                ];
            }
        }

        if ($this->stopwatch) {
            $stats['assets'] = $assets;
        }

        $this->assets = [
            'js' => array_unique(array_merge($assets['js'], $this->assets['js'])),
            'css' => array_unique(array_merge($assets['css'], $this->assets['css'])),
        ];
    }

    /**
     * @return array
     *
     * @internal since sonata-project/block-bundle 3.16
     */
    protected function startTracing(BlockInterface $block)
    {
        if (null !== $this->stopwatch) {
            $this->traces[$block->getId()] = $this->stopwatch->start(
                sprintf('%s (id: %s, type: %s)', $block->getName(), $block->getId(), $block->getType())
            );
        }

        return [
            'name' => $block->getName(),
            'type' => $block->getType(),
            'duration' => false,
            'memory_start' => memory_get_usage(true),
            'memory_end' => false,
            'memory_peak' => false,
            'cache' => [
                'keys' => [],
                'contextual_keys' => [],
                'handler' => false,
                'from_cache' => false,
                'ttl' => 0,
                'created_at' => false,
                'lifetime' => 0,
                'age' => 0,
            ],
            'assets' => [
                'js' => [],
                'css' => [],
            ],
        ];
    }

    /**
     * @internal since sonata-project/block-bundle 3.16
     */
    protected function stopTracing(BlockInterface $block, array $stats)
    {
        $e = $this->traces[$block->getId()]->stop();

        $this->traces[$block->getId()] = array_merge($stats, [
            'duration' => $e->getDuration(),
            'memory_end' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
        ]);

        $this->traces[$block->getId()]['cache']['lifetime'] = $this->traces[$block->getId()]['cache']['age'] + $this->traces[$block->getId()]['cache']['ttl'];
    }

    /**
     * @return array
     *
     * @internal since sonata-project/block-bundle 3.16
     */
    protected function getEventBlocks(BlockEvent $event)
    {
        $results = [];

        foreach ($event->getBlocks() as $block) {
            $results[] = [$block->getId(), $block->getType()];
        }

        return $results;
    }

    /**
     * @param string $eventName
     *
     * @return array
     *
     * @internal since sonata-project/block-bundle 3.16
     */
    protected function getEventListeners($eventName)
    {
        $results = [];

        foreach ($this->eventDispatcher->getListeners($eventName) as $listener) {
            if ($listener instanceof \Closure) {
                $results[] = '{closure}()';
            } elseif (\is_object($listener[0])) {
                $results[] = \get_class($listener[0]);
            } elseif (\is_string($listener[0])) {
                $results[] = $listener[0];
            } else {
                $results[] = 'Unknown type!';
            }
        }

        return $results;
    }

    /**
     * @return CacheAdapterInterface|false
     *
     * @internal since sonata-project/block-bundle 3.16
     */
    protected function getCacheService(BlockInterface $block, array &$stats = null)
    {
        if (!$this->cacheManager) {
            return false;
        }

        // type by block class
        $class = ClassUtils::getClass($block);
        $cacheServiceId = isset($this->cacheBlocks['by_class'][$class]) ? $this->cacheBlocks['by_class'][$class] : false;

        // type by block service
        if (!$cacheServiceId) {
            $cacheServiceId = isset($this->cacheBlocks['by_type'][$block->getType()]) ? $this->cacheBlocks['by_type'][$block->getType()] : false;
        }

        if (!$cacheServiceId) {
            return false;
        }

        if ($this->stopwatch) {
            $stats['cache']['handler'] = $cacheServiceId;
        }

        return $this->cacheManager->getCacheService($cacheServiceId);
    }

    /**
     * @param array<string, mixed> $stats
     */
    private function getCachedBlock(BlockContextInterface $blockContext, BlockServiceInterface $service, array &$stats): ?Response
    {
        $cacheKeys = $this->getCacheKey($service, $blockContext);

        if (null !== $this->cachePool) {
            $item = $this->cachePool->getItem(json_encode($cacheKeys));

            return $item->get();
        }

        $cacheService = $this->getCacheService($blockContext->getBlock(), $stats);

        if (!$cacheService) {
            return null;
        }

        if ($this->stopwatch) {
            $stats['cache']['keys'] = $cacheKeys;
        }

        // Please note, some cache handler will always return true (js for instance)
        // This will allows to have a non cacheable block, but the global page can still be cached by
        // a reverse proxy, as the generated page will never get the generated Response from the block.
        if ($cacheService->has($cacheKeys)) {
            $cacheElement = $cacheService->get($cacheKeys);

            if ($this->stopwatch) {
                $stats['cache']['from_cache'] = false;
            }

            if (!$cacheElement->isExpired() && $cacheElement->getData() instanceof Response) {
                /* @var Response $response */

                if ($this->stopwatch) {
                    $stats['cache']['from_cache'] = true;
                }

                return $cacheElement->getData();
            }
        }

        return null;
    }

    private function saveCache(BlockContextInterface $blockContext, BlockServiceInterface $service, Response $response, array $contextualKeys): void
    {
        if (!$response->isCacheable()) {
            return;
        }

        $cacheKeys = $this->getCacheKey($service, $blockContext);

        if (null !== $this->cachePool) {
            $item = $this->cachePool->getItem(json_encode($cacheKeys));
            $item->set($response);
            $item->expiresAfter((int) $response->getTtl());

            $this->cachePool->save($item);

            return;
        }

        $cacheService = $this->getCacheService($blockContext->getBlock(), $stats);

        if (!$cacheService) {
            return;
        }

        $cacheService->set($cacheKeys, $response, (int) $response->getTtl(), $contextualKeys);
    }

    private function getCacheKey(BlockServiceInterface $service, BlockContextInterface $blockContext): array
    {
        return array_merge(
            $service->getCacheKeys($blockContext->getBlock()),
            $blockContext->getSetting('extra_cache_keys')
        );
    }
}
