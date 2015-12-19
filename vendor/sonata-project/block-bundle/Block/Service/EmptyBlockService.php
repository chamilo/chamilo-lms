<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Block\Service;

use Sonata\BlockBundle\Block\BlockContextInterface;
use Symfony\Component\HttpFoundation\Response;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\AdminBundle\Validator\ErrorElement;
use Sonata\BlockBundle\Block\BaseBlockService;

class EmptyBlockService extends BaseBlockService
{
    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $form, BlockInterface $block)
    {
        throw new \RuntimeException('Not used, this block renders an empty result if no block document can be found');
    }

    /**
     * {@inheritdoc}
     */
    public function validateBlock(ErrorElement $errorElement, BlockInterface $block)
    {
        throw new \RuntimeException('Not used, this block renders an empty result if no block document can be found');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        return new Response();
    }
}
