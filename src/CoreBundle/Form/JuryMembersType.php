<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JuryMembersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'role',
            'entity',
            [
                'class' => 'Entity\Role',
                'property' => 'name',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.role LIKE :role')
                        ->setParameter(':role', 'ROLE_JURY%')
                        ->orderBy('u.name', Criteria::DESC)
                    ;
                },
            ]
        );

        $builder->add('user_id', ChoiceType::class, [
            'label' => 'User',
        ]);
        $builder->add('jury_id', 'hidden');
        $builder->add('submit', SubmitType::class);

        $factory = $builder->getFormFactory();

        // Fixes issue with the ajax select, waiting this workaround until symfony add ajax search into the core
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function ($event) use ($factory): void {
                $form = $event->getForm();
                $case = $event->getData();
                $id = $case['user_id'][0];

                if ($case) {
                    $form->remove('user_id');
                    $form->add(
                        $factory->createNamed(
                            'user_id',
                            HiddenType::class,
                            $id,
                            [
                                'auto_initialize' => false,
                            ]
                        )
                    );
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Chamilo\CoreBundle\Entity\JuryMembers',
            ]
        );
    }

    public function getName(): string
    {
        return 'jury_user';
    }
}
