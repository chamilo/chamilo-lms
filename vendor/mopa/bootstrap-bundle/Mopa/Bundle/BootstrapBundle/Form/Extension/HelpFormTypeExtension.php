<?php
namespace Mopa\Bundle\BootstrapBundle\Form\Extension;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Extension for Help Forms handling
 *
 * @author phiamo <phiamo@googlemail.com>
 *
 */
class HelpFormTypeExtension extends AbstractTypeExtension
{
    /**
     * @var array
     */
    protected $options = array();

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
        $view->vars['help_block'] = $options['help_block'];
        $view->vars['help_label'] = $options['help_label'];
        if ($options['help_label_tooltip'] != null && !is_array($options['help_label_tooltip'])) {
            throw new InvalidArgumentException('The "help_label_tooltip" option must be an "array".');
        } elseif ($options['help_label_tooltip']) {
            if (!isset($options['help_label_tooltip']['title'])) {
                $options['help_label_tooltip']['title'] = $this->options['help_label_tooltip']['title'];
            }
            if (!isset($options['help_label_tooltip']['text'])) {
                $options['help_label_tooltip']['text'] = $this->options['help_label_tooltip']['text'];
            }
            if (!isset($options['help_label_tooltip']['icon'])) {
                $options['help_label_tooltip']['icon'] = $this->options['help_label_tooltip']['icon'];
            }
            if (!isset($options['help_label_tooltip']['placement'])) {
                $options['help_label_tooltip']['placement'] = $this->options['help_label_tooltip']['placement'];
            }

        }
        if ($options['help_label_popover'] != null && !is_array($options['help_label_popover'])) {
            throw new InvalidArgumentException('The "help_label_popover" option must be an "array".');
        } elseif ($options['help_label_popover']) {
            if (!isset($options['help_label_popover']['title'])) {
                $options['help_label_popover']['title'] = $this->options['help_label_popover']['title'];
            }
            if (!isset($options['help_label_popover']['text'])) {
                $options['help_label_popover']['text'] = $this->options['help_label_popover']['text'];
            }
            if (!isset($options['help_label_popover']['content'])) {
                $options['help_label_popover']['content'] = $this->options['help_label_popover']['content'];
            }
            if (!isset($options['help_label_popover']['icon'])) {
                $options['help_label_popover']['icon'] = $this->options['help_label_popover']['icon'];
            }
            if (!isset($options['help_label_popover']['placement'])) {
                $options['help_label_popover']['placement'] = $this->options['help_label_popover']['placement'];
            }
        }
        if ($options['help_widget_popover'] != null && !is_array($options['help_widget_popover'])) {
            throw new InvalidArgumentException('The "help_widget_popover" option must be an "array".');
        } elseif ($options['help_widget_popover']) {
            if (!isset($options['help_widget_popover']['title'])) {
                $options['help_widget_popover']['title'] = $this->options['help_widget_popover']['title'];
            }
            if (!isset($options['help_widget_popover']['content'])) {
                $options['help_widget_popover']['content'] = $this->options['help_widget_popover']['content'];
            }
            if (!isset($options['help_widget_popover']['toggle'])) {
                $options['help_widget_popover']['toggle'] = $this->options['help_widget_popover']['toggle'];
            }
            if (!isset($options['help_widget_popover']['trigger'])) {
                $options['help_widget_popover']['trigger'] = $this->options['help_widget_popover']['trigger'];
            }
            if (!isset($options['help_widget_popover']['placement'])) {
                $options['help_widget_popover']['placement'] = $this->options['help_widget_popover']['placement'];
            }
        }
        $view->vars['help_label_tooltip'] = $options['help_label_tooltip'];
        $view->vars['help_label_popover'] = $options['help_label_popover'];
        $view->vars['help_widget_popover'] = $options['help_widget_popover'];
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'help_block' => null,
            'help_label' => null,
            'help_label_tooltip' => $this->options['help_label_tooltip'],
            'help_label_popover' => $this->options['help_label_popover'],
            'help_widget_popover' => $this->options['help_widget_popover']
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
