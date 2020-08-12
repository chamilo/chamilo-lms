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
use Sonata\BlockBundle\Meta\Metadata;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\CoreBundle\Validator\ErrorElement;

@trigger_error(
    'The '.__NAMESPACE__.'\AbstractAdminBlockService class is deprecated since sonata-project/block-bundle 3.16 '.
    'and will be removed with the 4.0 release.',
    E_USER_DEPRECATED
);

/**
 * @author Christian Gripp <mail@core23.de>
 *
 * @deprecated since sonata-project/block-bundle 3.16 without any replacement
 */
abstract class AbstractAdminBlockService extends AbstractBlockService implements AdminBlockServiceInterface
{
    public function buildCreateForm(FormMapper $formMapper, BlockInterface $block)
    {
        $this->buildEditForm($formMapper, $block);
    }

    public function prePersist(BlockInterface $block)
    {
    }

    public function postPersist(BlockInterface $block)
    {
    }

    public function preUpdate(BlockInterface $block)
    {
    }

    public function postUpdate(BlockInterface $block)
    {
    }

    public function preRemove(BlockInterface $block)
    {
    }

    public function postRemove(BlockInterface $block)
    {
    }

    public function buildEditForm(FormMapper $form, BlockInterface $block)
    {
    }

    public function validateBlock(ErrorElement $errorElement, BlockInterface $block)
    {
    }

    public function getBlockMetadata($code = null)
    {
        return new Metadata($this->getName(), (null !== $code ? $code : $this->getName()), false, 'SonataBlockBundle', ['class' => 'fa fa-file']);
    }
}
