<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CurriculumCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $entity = $builder->getData();

        $builder->add('title', TextType::class);
        $builder->add('max_score', TextType::class);
        $builder->add('min_chars', TextType::class);
        $builder->add('min_chars', TextType::class);

        $builder->add('c_id', HiddenType::class);
        $builder->add('session_id', HiddenType::class);

        $course = $entity->getCourse();
        $session = $entity->getSession();

        $builder->add(
            'parent',
            'entity',
            [
                'class' => 'Entity\CurriculumCategory',
                'query_builder' => function ($repository) use (
                    $course,
                    $session
                ) {
                    $qb = $repository->createQueryBuilder('c')
                        ->where('c.cId = :id')
                        ->orderBy('c.title', 'ASC')
                    ;
                    $parameters = [
                        'id' => $course->getId(),
                    ];

                    if (!empty($session)) {
                        $qb->andWhere('c.sessionId = :session_id');
                        $parameters['session_id'] = $session->getId();
                    }
                    $qb->setParameters($parameters);

                    return $qb;
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
                'data_class' => 'Chamilo\CoreBundle\Entity\CurriculumCategory',
            ]
        );
    }

    public function getName(): string
    {
        return 'curriculumCategory';
    }
}
