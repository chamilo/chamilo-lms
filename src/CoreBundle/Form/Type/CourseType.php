<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form\Type;

use Chamilo\CoreBundle\Entity\Course;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // @todo as a service to load the preferred_choices
        $builder
            ->add('title', TextType::class)
            //->add('code', 'text')
            ->add(
                'course_language',
                LocaleType::class,
                [
                    'preferred_choices' => ['en', 'fr', 'es'],
                ]
            )
            ->add(
                'visibility',
                ChoiceType::class,
                [
                    'choices' => Course::getStatusList(),
                ]
            )
            ->add('department_name', TextType::class, [
                'required' => false,
            ])
            ->add('department_url', UrlType::class, [
                'required' => false,
            ])
            //->add('disk_quota', 'text')
            ->add(
                'expiration_date',
                DateType::class,
                [
                    'required' => false,
                ]
            )
            /* ->add('general_coach', 'entity', array(
                 'class' => 'ChamiloCoreBundle:User',
                 'property' => 'username',
             ))
             ->add('session_admin_id',  'entity', array(
                 'class' => 'ChamiloCoreBundle:User',
                 'property' => 'username',
             ))
             ->add('visibility', 'choice',
                 array('choices' => Session::getStatusList())
             )
             ->add('session_category_id', 'entity', array(
                 'class' => 'ChamiloCoreBundle:SessionCategory',
                 'property' => 'name',
             ))
             ->add('promotion_id', 'entity', array(
                 'class' => 'ChamiloCoreBundle:Promotion',
                 'property' => 'name',
             ))*/
            /*
            ->add('coach_access_end_date', 'sonata_type_datetime_picker')*/
            ->add('save', SubmitType::class, [
                'label' => 'Add',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Course::class,
            ]
        );
    }

    public function getName(): string
    {
        return 'course';
    }
}
