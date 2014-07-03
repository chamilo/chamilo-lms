<?php
namespace Mopa\Bundle\BootstrapBundle\Form\Extension;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Exception\InvalidArgumentException;

/**
 * Extension for collections
 *
 * @author phiamo <phiamo@googlemail.com>
 *
 */
class WidgetCollectionFormTypeExtension extends AbstractTypeExtension
{
    protected $options;

    /**
     * Construct extension
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (in_array('collection', $view->vars['block_prefixes'])) {
            if ($options['widget_add_btn'] != null && !is_array($options['widget_add_btn'])) {
                throw new InvalidArgumentException('The "widget_add_btn" option must be an "array".');
            } elseif ((isset($options['allow_add']) && true === $options['allow_add']) || $options['widget_add_btn']) {
                if (isset($options['widget_add_btn']['attr']) && !is_array($options['widget_add_btn']['attr'])) {
                    throw new InvalidArgumentException('The "widget_add_btn.attr" option must be an "array".');
                }
                if (!isset($options['widget_add_btn']['attr'])) {
                    $options['widget_add_btn']['attr'] = $this->options['widget_add_btn']['attr'];
                }
                if (!isset($options['widget_add_btn']['label'])) {
                    $options['widget_add_btn']['label'] = $this->options['widget_add_btn']['label'];
                }
                if (!isset($options['widget_add_btn']['icon'])) {
                    $options['widget_add_btn']['icon'] = $this->options['widget_add_btn']['icon'];
                }
                if (!isset($options['widget_add_btn']['icon_color'])) {
                    $options['widget_add_btn']['icon_color'] = $this->options['widget_add_btn']['icon_color'];
                }
            }
        }
        if ($view->parent && in_array('collection', $view->parent->vars['block_prefixes'])) {

            if ($options['widget_remove_btn'] != null && !is_array($options['widget_remove_btn'])) {
                throw new InvalidArgumentException('The "widget_remove_btn" option must be an "array".');
            } elseif ((isset($options['allow_delete']) && true === $options['allow_delete']) || $options['widget_remove_btn']) {
                if (isset($options['widget_remove_btn']) && !is_array($options['widget_remove_btn'])) {
                    throw new InvalidArgumentException('The "widget_remove_btn" option must be an "array".');
                }
                if (!isset($options['widget_remove_btn']['attr'])) {
                    $options['widget_remove_btn']['attr'] = $this->options['widget_remove_btn']['attr'];
                }
                if (!isset($options['widget_remove_btn']['label'])) {
                    $options['widget_remove_btn']['label'] = $this->options['widget_remove_btn']['label'];
                }
                if (!isset($options['widget_remove_btn']['icon'])) {
                    $options['widget_remove_btn']['icon'] = $this->options['widget_remove_btn']['icon'];
                }
                if (!isset($options['widget_remove_btn']['icon_color'])) {
                    $options['widget_remove_btn']['icon_color'] = $this->options['widget_remove_btn']['icon_color'];
                }
            }
        }
        $view->vars['omit_collection_item'] = $options['omit_collection_item'];
        $view->vars['widget_add_btn'] = $options['widget_add_btn'];
        $view->vars['widget_remove_btn'] = $options['widget_remove_btn'];
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'omit_collection_item' => true === $this->options['render_collection_item'] ? false : true,
            'widget_add_btn' => $this->options['widget_add_btn'],
            'widget_remove_btn' => $this->options['widget_remove_btn'],
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'form';
    }
}
