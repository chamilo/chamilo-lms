<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use DateTime;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JuryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class);
        $builder->add(
            'opening_date',
            'datetime',
            [
                'data' => new DateTime(),
            ]
        );

        $builder->add(
            'closure_date',
            'datetime',
            [
                'data' => new DateTime(),
            ]
        );

        $builder->add(
            'branch',
            'entity',
            [
                'class' => 'Entity\BranchSync',
                'property' => 'branchName',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                            //->where('u.role LIKE :role')
                            //->setParameter(':role', 'ROLE_JURY%')
                        ->orderBy('u.branchName', \Doctrine\Common\Collections\Criteria::DESC)
                    ;
                },
            ]
        );

        $builder->add('opening_user_id', TextType::class);
        $builder->add('closure_user_id', TextType::class);
        $builder->add('exercise_id', TextType::class);

        //$builder->add('users', 'collection', array('type' => new JuryType()));

        $builder->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Chamilo\CoreBundle\Entity\Jury',
            ]
        );
    }

    public function getName(): string
    {
        return 'jury';
    }
}
