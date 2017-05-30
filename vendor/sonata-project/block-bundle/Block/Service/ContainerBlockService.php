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
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\CoreBundle\Model\Metadata;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Render children pages.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class ContainerBlockService extends AbstractAdminBlockService
{
    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
        $formMapper->add('enabled');

        $formMapper->add('settings', 'sonata_type_immutable_array', array(
            'keys' => array(
                array('code', 'text', array('required' => false)),
                array('layout', 'textarea', array()),
                array('class', 'text', array('required' => false)),
                array('template', 'sonata_type_container_template_choice', array()),
            ),
        ));

        $formMapper->add('children', 'sonata_type_collection', array(), array(
            'admin_code' => 'sonata.page.admin.block',
            'edit' => 'inline',
            'inline' => 'table',
            'sortable' => 'position',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        return $this->renderResponse($blockContext->getTemplate(), array(
            'block' => $blockContext->getBlock(),
            'decorator' => $this->getDecorator($blockContext->getSetting('layout')),
            'settings' => $blockContext->getSettings(),
        ), $response);
    }

    /**
     * {@inheritdoc}
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'code' => '',
            'layout' => '{{ CONTENT }}',
            'class' => '',
            'template' => 'SonataBlockBundle:Block:block_container.html.twig',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockMetadata($code = null)
    {
        return new Metadata($this->getName(), (!is_null($code) ? $code : $this->getName()), false, 'SonataBlockBundle', array(
            'class' => 'fa fa-square-o',
        ));
    }

    /**
     * Returns a decorator object/array from the container layout setting.
     *
     * @param string $layout
     *
     * @return array
     */
    protected function getDecorator($layout)
    {
        $key = '{{ CONTENT }}';
        if (strpos($layout, $key) === false) {
            return array();
        }

        $segments = explode($key, $layout);
        $decorator = array(
            'pre' => isset($segments[0]) ? $segments[0] : '',
            'post' => isset($segments[1]) ? $segments[1] : '',
        );

        return $decorator;
    }
}
