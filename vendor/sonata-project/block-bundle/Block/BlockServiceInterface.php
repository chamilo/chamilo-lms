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

use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Interface BlockServiceInterface.
 */
interface BlockServiceInterface
{
    /**
     * @param BlockContextInterface $blockContext
     * @param Response              $response
     *
     * @return Response
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null);

    /**
     * @return string
     */
    public function getName();

    /**
     * Define the default options for the block.
     *
     * @param OptionsResolverInterface $resolver
     *
     * @deprecated since version 2.3, to be renamed in 3.0.
     *             Use the method configureSettings instead.
     *             This method will be added to the BlockServiceInterface with SonataBlockBundle 3.0.
     */
    public function setDefaultSettings(OptionsResolverInterface $resolver);

    /**
     * @param BlockInterface $block
     */
    public function load(BlockInterface $block);

    /**
     * @param $media
     *
     * @return array
     */
    public function getJavascripts($media);

    /**
     * @param $media
     *
     * @return array
     */
    public function getStylesheets($media);

    /**
     * @param BlockInterface $block
     *
     * @return array
     */
    public function getCacheKeys(BlockInterface $block);
}
