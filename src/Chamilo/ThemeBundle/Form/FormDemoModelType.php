<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FormDemoModelType.
 *
 * @package Chamilo\ThemeBundle\Form
 */
class FormDemoModelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options = [
            'opt1' => 'This is option 1',
            'opt2' => 'This is option 2',
            'opt3' => 'This is option 3',
        ];

        $choices = [
            'choice1' => 'This is choice 1',
            'choice2' => 'This is choice 2',
            'choice3' => 'This is choice 3',
        ];

        $builder->add('name', 'text')
                ->add('gender', 'choice', ['choices' => ['m' => 'male', 'f' => 'female']])
                ->add('someOption', 'choice', ['choices' => $options, 'expanded' => true])
                ->add('someChoices', 'choice', ['choices' => $choices, 'expanded' => true, 'multiple' => true])
                ->add('username')
                ->add('email')
                ->add('termsAccepted', 'checkbox')
                ->add('message', 'textarea')
                ->add('price')
                ->add('date', 'date', ['widget' => 'single_text'])
                ->add('time', 'time', ['widget' => 'single_text'])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
                'data_class' => 'Chamilo\ThemeBundle\Model\FormDemoModel',
            ]);
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'form_demo';
    }
}
