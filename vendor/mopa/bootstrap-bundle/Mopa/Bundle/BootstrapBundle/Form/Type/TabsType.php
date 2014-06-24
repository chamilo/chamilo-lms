<?php

namespace Mopa\Bundle\BootstrapBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Type for Tabs handling
 *
 * @author phiamo <phiamo@googlemail.com>
 *
 */
class TabsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['tabs'] = $options['tabs'];
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'tabs' => array(),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'tabs';
    }
}
