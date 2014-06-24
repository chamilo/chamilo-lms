<?php
namespace Mopa\Bundle\BootstrapBundle\Form\Extension;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Extension for Horizontal Forms handling
 *
 * @author phiamo <phiamo@googlemail.com>
 *
 */
class HorizontalFormTypeExtension extends AbstractTypeExtension
{
    protected $options;

    /**
     * {@inheritdoc}
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
        $view->vars['inline'] = $options['inline'];
        $view->vars['horizontal'] = $options['horizontal'];
        $view->vars['horizontal_label_class'] = $options['horizontal_label_class'];
        $view->vars['horizontal_label_offset_class'] = $options['horizontal_label_offset_class'];
        $view->vars['horizontal_input_wrapper_class'] = $options['horizontal_input_wrapper_class'];

    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'inline' => false, // for BC compat setting this to true
                'horizontal' => true, // for BC compat setting this to true
                'horizontal_label_class' => $this->options['horizontal_label_class'],
                'horizontal_label_offset_class' => $this->options['horizontal_label_offset_class'],
                'horizontal_input_wrapper_class' => $this->options['horizontal_input_wrapper_class'],
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'form';
    }
}
