<?php
namespace Mopa\Bundle\BootstrapBundle\Form\Extension;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormFactoryInterface;
use Mopa\Bundle\BootstrapBundle\Form\Type\TabsType;

/**
 * Adding Tabs to FormTypes
 */
class TabbedFormTypeExtension extends AbstractTypeExtension
{
    private $formFactory;
    private $options;

    /**
     * Construct extension
     *
     * @param FormFactoryInterface $formFactory
     * @param array                $options
     */
    public function __construct(FormFactoryInterface $formFactory, array $options)
    {
        $this->formFactory = $formFactory;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'form';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'tabs_class' => $this->options['class'],
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['tabbed'] = false;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!$view->vars['tabbed']) {
            return;
        }

        $activeTab = null;
        $tabIndex = 0;
        $foundInvalid = false;
        $tabs = array();

        foreach ($view->children as $child) {
            if (in_array('tab', $child->vars['block_prefixes'])) {

                $child->vars['tab_index'] = $tabIndex;
                $valid = $child->vars['valid'];

                if (($activeTab === null || !$valid) && !$foundInvalid) {
                    $activeTab = $child;
                    $foundInvalid = !$valid;
                }

                $tabs[$tabIndex] = array(
                    'id' => $child->vars['id'],
                    'label' => $child->vars['label'],
                    'icon' => $child->vars['icon'],
                    'active' => false,
                );

                $tabIndex++;
            }
        }

        $activeTab->vars['tab_active'] = true;
        $tabs[$activeTab->vars['tab_index']]['active'] = true;

        $tabsForm = $this->formFactory->create(new TabsType(), null, array(
            'tabs' => $tabs,
            'attr' => array(
                'class' => $options['tabs_class'],
            ),
        ));

        $view->vars['tabs'] = $tabs;
        $view->vars['tabbed'] = true;
        $view->vars['tabsView'] = $tabsForm->createView();
    }
}
