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

namespace Sonata\BlockBundle\Command;

use Sonata\BlockBundle\Block\BlockServiceManagerInterface;
use Symfony\Component\Console\Command\Command;

/**
 * @deprecated since sonata-project/block-bundle 3.16, to be removed in 4.0
 */
abstract class BaseCommand extends Command
{
    /**
     * @var BlockServiceManagerInterface
     */
    protected $blockManager;

    public function __construct(string $name = null, BlockServiceManagerInterface $blockManager = null)
    {
        // NEXT_MAJOR: Remove the default value for argument 2 and the following condition
        if (null === $blockManager) {
            throw new \InvalidArgumentException(sprintf(
                'Argument 2 passed to %s::%s() must be an instance of %s, %s given.',
                static::class,
                __FUNCTION__,
                BlockServiceManagerInterface::class,
                \gettype($blockManager)
            ));
        }

        $this->blockManager = $blockManager;

        parent::__construct($name);
    }

    /**
     * @return BlockServiceManagerInterface
     */
    public function getBlockServiceManager()
    {
        // NEXT_MAJOR: Remove this method
        @trigger_error(sprintf(
            'Method %1$s::%2$s() is deprecated since sonata-project/block-bundle 3.16 and will be removed with the 4.0 release.'.
            'Use the %1$s::$blockManager property instead.',
            static::class,
            __FUNCTION__
        ), E_USER_DEPRECATED);

        return $this->blockManager;
    }
}
