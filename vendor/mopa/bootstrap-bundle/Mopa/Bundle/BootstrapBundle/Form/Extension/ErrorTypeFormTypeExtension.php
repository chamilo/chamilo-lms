<?php
namespace Mopa\Bundle\BootstrapBundle\Form\Extension;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Extension for Error handling
 *
 * @author phiamo <phiamo@googlemail.com>
 *
 */
class ErrorTypeFormTypeExtension extends AbstractTypeExtension
{
    protected $errorType;

    /**
     * Construct extension
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->errorType = $options['error_type'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['error_type'] = $options['error_type'];
        $view->vars['error_delay'] = $options['error_delay'];
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'error_type' => $this->errorType,
            'error_delay'=> false
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
