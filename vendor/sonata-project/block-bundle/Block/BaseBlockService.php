<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Block;

use Sonata\AdminBundle\Validator\ErrorElement;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * BaseBlockService
 *
 *
 * @author     Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
abstract class BaseBlockService implements BlockServiceInterface
{
    protected $name;

    protected $templating;

    /**
     * @param string          $name
     * @param EngineInterface $templating
     */
    public function __construct($name, EngineInterface $templating)
    {
        $this->name       = $name;
        $this->templating = $templating;
    }

    /**
     * Returns a Response object than can be cacheable
     *
     * @param string   $view
     * @param array    $parameters
     * @param Response $response
     *
     * @return Response
     */
    public function renderResponse($view, array $parameters = array(), Response $response = null)
    {
        return $this->getTemplating()->renderResponse($view, $parameters, $response);
    }

    /**
     * Returns a Response object that cannot be cacheable, this must be used if the Response is related to the user.
     * A good solution to make the page cacheable is to configure the block to be cached with javascript ...
     *
     * @param string   $view
     * @param array    $parameters
     * @param Response $response
     *
     * @return Response
     */
    public function renderPrivateResponse($view, array $parameters = array(), Response $response = null)
    {
        return $this->renderResponse($view, $parameters, $response)
            ->setTtl(0)
            ->setPrivate()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

     /**
     * {@inheritdoc}
     */
    public function getTemplating()
    {
        return $this->templating;
    }

    /**
     * {@inheritdoc}
     */
    public function buildCreateForm(FormMapper $formMapper, BlockInterface $block)
    {
        $this->buildEditForm($formMapper, $block);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeys(BlockInterface $block)
    {
        return array(
            'block_id'   => $block->getId(),
            'updated_at' => $block->getUpdatedAt() ? $block->getUpdatedAt()->format('U') : strtotime('now'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function prePersist(BlockInterface $block)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function postPersist(BlockInterface $block)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(BlockInterface $block)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function postUpdate(BlockInterface $block)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function preRemove(BlockInterface $block)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function postRemove(BlockInterface $block)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function load(BlockInterface $block)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getJavascripts($media)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getStylesheets($media)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultSettings(OptionsResolverInterface $resolver)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        return $this->renderResponse($blockContext->getTemplate(), array(
            'block_context'  => $blockContext,
            'block'          => $blockContext->getBlock(),
        ), $response);
    }

    /**
     * @param FormMapper     $form
     * @param BlockInterface $block
     *
     * @return void
     */
    public function buildEditForm(FormMapper $form, BlockInterface $block)
    {

    }

    /**
     * @param ErrorElement   $errorElement
     * @param BlockInterface $block
     *
     * @return void
     */
    public function validateBlock(ErrorElement $errorElement, BlockInterface $block)
    {

    }
}
