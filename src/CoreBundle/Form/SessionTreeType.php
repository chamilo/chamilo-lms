<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SessionTreeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'type',
            ChoiceType::class,
            [
                'choices' => ['1', '2', '3', '4'],
            ]
        );

        $builder->add(
            'sessionPath',
            'entity',
            [
                'class' => 'Entity\SessionPath',
                'property' => 'name',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.name', Criteria::DESC)
                    ;
                },
            ]
        );

        $builder->add(
            'tool',
            'entity',
            [
                'class' => 'Entity\Tool',
                'property' => 'name',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.name', Criteria::DESC)
                    ;
                },
            ]
        );

        $builder->add(
            'tool',
            'entity',
            [
                'class' => 'Entity\Tool',
                'property' => 'name',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.name', Criteria::DESC)
                    ;
                },
            ]
        );

        $builder->add(
            'session',
            EntityType::class,
            [
                'class' => 'Entity\Session',
                'property' => 'name',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.name', Criteria::DESC)
                    ;
                },
            ]
        );

        $builder->add(
            'course',
            EntityType::class,
            [
                'class' => 'Entity\Course',
                'property' => 'title',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.title', Criteria::DESC)
                    ;
                },
            ]
        );
        $builder
            ->add('submit', 'submit')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Chamilo\CoreBundle\Entity\SessionTree',
            ]
        );
    }

    public function getName(): string
    {
        return 'sessionPath';
    }
}
