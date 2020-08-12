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

namespace Sonata\BlockBundle\Block;

use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Interface BlockServiceInterface.
 *
 * NEXT_MAJOR: remove this interface.
 *
 * @deprecated since sonata-project/block-bundle 3.2, to be removed with 4.0
 */
interface BlockServiceInterface
{
    /**
     * @return Response
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null);

    /**
     * @deprecated since sonata-project/block-bundle 3.16, to be removed in 4.0
     *
     * @return string
     */
    public function getName();

    /**
     * Define the default options for the block.
     *
     * NEXT_MAJOR: rename this method.
     *
     * @deprecated since sonata-project/block-bundle 2.3, to be renamed in 4.0.
     *             Use the method configureSettings instead.
     *             This method will be added to the BlockServiceInterface with SonataBlockBundle 4.0
     */
    public function setDefaultSettings(OptionsResolverInterface $resolver);

    public function load(BlockInterface $block);

    /**
     * @deprecated since sonata-project/block-bundle 3.13.0, to be removed in 4.0
     *
     * @param string $media
     *
     * @return array
     */
    public function getJavascripts($media);

    /**
     * @deprecated since sonata-project/block-bundle 3.13.0, to be removed in 4.0
     *
     * @param string $media
     *
     * @return array
     */
    public function getStylesheets($media);

    /**
     * @return array
     */
    public function getCacheKeys(BlockInterface $block);
}
