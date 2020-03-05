<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form\Resource;

use Chamilo\CourseBundle\Entity\CLinkCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CLinkCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('category_title')
            ->add('description', TextareaType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => CLinkCategory::class,
            ]
        );
    }

    public function getName()
    {
        return 'chamilo_link_category';
    }
}
