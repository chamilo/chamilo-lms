<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Chamilo\CourseBundle\Entity\CTool;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CourseHomeToolType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class);
        $builder->add('link', TextType::class);
        $builder->add(
            'custom_icon',
            'file',
            [
                'required' => false,
                'data_class' => null,
            ]
        );
        $builder->add(
            'target',
            'choice',
            [
                'choices' => ['_self', '_blank'],
            ]
        );
        $builder->add(
            'visibility',
            'choice',
            [
                'choices' => ['1', '0'],
            ]
        );
        $builder->add('c_id', HiddenType::class);
        $builder->add('session_id', HiddenType::class);

        $builder->add('description', TextareaType::class);
        $builder->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => CTool::class,
            ]
        );
    }

    public function getName(): string
    {
        return 'courseHomeTool';
    }
}
