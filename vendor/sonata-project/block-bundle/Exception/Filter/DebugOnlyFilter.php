<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Exception\Filter;

use Sonata\BlockBundle\Model\BlockInterface;

/**
 * This filter handles exceptions only when debug mode is enabled.
 *
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
class DebugOnlyFilter implements FilterInterface
{
    /**
     * @var bool
     */
    protected $debug;

    /**
     * Constructor.
     *
     * @param bool $debug
     */
    public function __construct($debug)
    {
        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(\Exception $exception, BlockInterface $block)
    {
        return $this->debug ? true : false;
    }
}
