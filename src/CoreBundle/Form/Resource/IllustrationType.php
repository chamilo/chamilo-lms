<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form\Resource;

use Chamilo\CoreBundle\Entity\Illustration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IllustrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Illustration::class,
            ]
        );
    }

    public function getName()
    {
        return 'chamilo_illustration';
    }
}
