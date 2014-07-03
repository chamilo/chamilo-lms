<?php
namespace Mopa\Bundle\BootstrapBundle\Form\Extension;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Extension for Legend handling
 *
 * @author phiamo <phiamo@googlemail.com>
 *
 */
class LegendFormTypeExtension extends AbstractTypeExtension
{
    private $renderFieldset;
    private $showLegend;
    private $showChildLegend;
    private $renderRequiredAsterisk;
    private $renderOptionalText;

    /**
     * Construct extension
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->renderFieldset = $options['render_fieldset'];
        $this->showLegend = $options['show_legend'];
        $this->showChildLegend = $options['show_child_legend'];
        $this->renderRequiredAsterisk = $options['render_required_asterisk'];
        $this->renderOptionalText = $options['render_optional_text'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['render_fieldset'] = $options['render_fieldset'];
        $view->vars['show_legend'] = $options['show_legend'];
        $view->vars['show_child_legend'] = $options['show_child_legend'];
        $view->vars['label_render'] = $options['label_render'];
        $view->vars['render_required_asterisk'] = $options['render_required_asterisk'];
        $view->vars['render_optional_text'] = $options['render_optional_text'];
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'render_fieldset' => $this->renderFieldset,
            'show_legend' => $this->showLegend,
            'show_child_legend' => $this->showChildLegend,
            'label_render' => true,
            'render_required_asterisk' => $this->renderRequiredAsterisk,
            'render_optional_text' => $this->renderOptionalText,
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
