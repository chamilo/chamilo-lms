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
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Form\Type\ContainerTemplateType;
use Sonata\BlockBundle\Meta\Metadata;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\Form\Type\CollectionType;
use Sonata\Form\Type\ImmutableArrayType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Render children pages.
 *
 * @final since sonata-project/block-bundle 3.0
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class ContainerBlockService extends AbstractAdminBlockService
{
    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
        $formMapper->add('enabled');

        $formMapper->add('settings', ImmutableArrayType::class, [
            'keys' => [
                ['code', TextType::class, [
                    'required' => false,
                    'label' => 'form.label_code',
                ]],
                ['layout', TextareaType::class, [
                    'label' => 'form.label_layout',
                ]],
                ['class', TextType::class, [
                    'required' => false,
                    'label' => 'form.label_class',
                ]],
                ['template', ContainerTemplateType::class, [
                    'label' => 'form.label_template',
                ]],
            ],
            'translation_domain' => 'SonataBlockBundle',
        ]);

        $formMapper->add('children', CollectionType::class, [], [
            'admin_code' => 'sonata.page.admin.block',
            'edit' => 'inline',
            'inline' => 'table',
            'sortable' => 'position',
        ]);
    }

    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        return $this->renderResponse($blockContext->getTemplate(), [
            'block' => $blockContext->getBlock(),
            'decorator' => $this->getDecorator($blockContext->getSetting('layout')),
            'settings' => $blockContext->getSettings(),
        ], $response);
    }

    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'code' => '',
            'layout' => '{{ CONTENT }}',
            'class' => '',
            'template' => '@SonataBlock/Block/block_container.html.twig',
        ]);
    }

    public function getBlockMetadata($code = null)
    {
        return new Metadata($this->getName(), (null !== $code ? $code : $this->getName()), false, 'SonataBlockBundle', [
            'class' => 'fa fa-square-o',
        ]);
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
        if (false === strpos($layout, $key)) {
            return [];
        }

        $segments = explode($key, $layout);
        $decorator = [
            'pre' => $segments[0] ?? '',
            'post' => $segments[1] ?? '',
        ];

        return $decorator;
    }
}
