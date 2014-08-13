<?php

namespace Chamilo\CoreBundle\Block;

use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\SeoBundle\Block\Breadcrumb\BaseBreadcrumbMenuBlockService;

/**
 * Class DefaultBreadcrumbBlockService
 * @package Sonata\ProductBundle\Block
 */
class DefaultBreadcrumbBlockService extends BaseBreadcrumbMenuBlockService
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'chamilo.corebundle.block.breadcrumb';
    }

    /**
     * {@inheritdoc}
     */
    protected function getMenu(BlockContextInterface $blockContext)
    {
        $menu = $this->getRootMenu($blockContext);

        $menu->addChild('root');

        return $menu;
    }
}
