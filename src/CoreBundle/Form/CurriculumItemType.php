<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CurriculumItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        //$builderData = $builder->getData();
        /*$parentIdDisabled = false;
        if (!empty($builderData)) {
            $parentIdDisabled = true;
        }*/

        $builder->add('title', TextareaType::class);
        $builder->add('score', TextType::class);
        $builder->add('max_repeat', TextType::class);

        $builder->add(
            'category',
            EntityType::class,
            [
                'class' => 'Entity\CurriculumCategory',
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('p')
                        ->orderBy('p.title', 'ASC')
                    ;
                },
                'property' => 'title',
                'required' => false,
            ]
        );
        $builder->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Entity\CurriculumItem',
            ]
        );
    }

    public function getName(): string
    {
        return 'curriculumItem';
    }
}
