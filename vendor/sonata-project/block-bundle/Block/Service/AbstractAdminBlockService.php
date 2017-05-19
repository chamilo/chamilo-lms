<?php

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
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\CoreBundle\Model\Metadata;
use Sonata\CoreBundle\Validator\ErrorElement;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

/**
 * @author Christian Gripp <mail@core23.de>
 */
abstract class AbstractAdminBlockService extends AbstractBlockService implements AdminBlockServiceInterface
{
    /**
     * @param string          $name
     * @param EngineInterface $templating
     */
    public function __construct($name, EngineInterface $templating)
    {
        parent::__construct($name, $templating);
    }

    /**
     * {@inheritdoc}
     */
    public function buildCreateForm(FormMapper $formMapper, BlockInterface $block)
    {
        $this->buildEditForm($formMapper, $block);
    }

    /**
     * @param BlockInterface $block
     */
    public function prePersist(BlockInterface $block)
    {
    }

    /**
     * @param BlockInterface $block
     */
    public function postPersist(BlockInterface $block)
    {
    }

    /**
     * @param BlockInterface $block
     */
    public function preUpdate(BlockInterface $block)
    {
    }

    /**
     * @param BlockInterface $block
     */
    public function postUpdate(BlockInterface $block)
    {
    }

    /**
     * @param BlockInterface $block
     */
    public function preRemove(BlockInterface $block)
    {
    }

    /**
     * @param BlockInterface $block
     */
    public function postRemove(BlockInterface $block)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $form, BlockInterface $block)
    {
    }

    /**
     * @param ErrorElement   $errorElement
     * @param BlockInterface $block
     */
    public function validateBlock(ErrorElement $errorElement, BlockInterface $block)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockMetadata($code = null)
    {
        return new Metadata($this->getName(), (!is_null($code) ? $code : $this->getName()), false, 'SonataBlockBundle', array('class' => 'fa fa-file'));
    }
}
