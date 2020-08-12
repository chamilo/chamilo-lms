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

namespace Sonata\BlockBundle\Block\Service;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\BlockBundle\Meta\MetadataInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\CoreBundle\Validator\ErrorElement;

@trigger_error(
    'The '.__NAMESPACE__.'\AdminBlockServiceInterface interface is deprecated since sonata-project/block-bundle 3.16 '.
    'and will be removed with the 4.0 release. '.
    'Use '.__NAMESPACE__.'\Service\EditableBlockService instead.',
    E_USER_DEPRECATED
);

/**
 * @author Christian Gripp <mail@core23.de>
 *
 * @deprecated since sonata-project/block-bundle 3.16, to be removed with 4.0
 */
interface AdminBlockServiceInterface extends BlockServiceInterface
{
    public function buildEditForm(FormMapper $form, BlockInterface $block);

    public function buildCreateForm(FormMapper $form, BlockInterface $block);

    public function validateBlock(ErrorElement $errorElement, BlockInterface $block);

    /**
     * @param string|null $code
     *
     * @return MetadataInterface
     */
    public function getBlockMetadata($code = null);
}
