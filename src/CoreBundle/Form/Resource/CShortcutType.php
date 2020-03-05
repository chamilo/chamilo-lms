<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form\Resource;

use Chamilo\CourseBundle\Entity\CShortcut;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CShortcutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => CShortcut::class,
            ]
        );
    }

    public function getName()
    {
        return 'chamilo_shortcut';
    }
}
