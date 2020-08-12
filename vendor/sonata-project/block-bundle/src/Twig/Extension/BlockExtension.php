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

namespace Sonata\BlockBundle\Twig\Extension;

use Sonata\BlockBundle\Templating\Helper\BlockHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @final since sonata-project/block-bundle 3.0
 */
class BlockExtension extends AbstractExtension
{
    /**
     * @var BlockHelper
     */
    protected $blockHelper;

    /**
     * BlockExtension constructor.
     */
    public function __construct(BlockHelper $blockHelper)
    {
        $this->blockHelper = $blockHelper;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction(
                'sonata_block_exists',
                [$this->blockHelper, 'exists']
            ),
            new TwigFunction(
                'sonata_block_render',
                [$this->blockHelper, 'render'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'sonata_block_render_event',
                [$this->blockHelper, 'renderEvent'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'sonata_block_include_javascripts',
                [$this->blockHelper, 'includeJavascripts'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'sonata_block_include_stylesheets',
                [$this->blockHelper, 'includeStylesheets'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    public function getName()
    {
        return 'sonata_block';
    }
}
