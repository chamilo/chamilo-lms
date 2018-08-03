<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class JuryMembersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
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
                            ->orderBy('u.name', 'DESC');
                },
            ]
        );

        $builder->add('user_id', 'choice', ['label' => 'User']);
        $builder->add('jury_id', 'hidden');
        $builder->add('submit', 'submit');

        $factory = $builder->getFormFactory();

        // Fixes issue with the ajax select, waiting this workaround until symfony add ajax search into the core
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function ($event) use ($factory, $builder) {
                $form = $event->getForm();
                $case = $event->getData();
                $id = $case['user_id'][0];

                if ($case) {
                    $form->remove('user_id');
                    $form->add(
                        $factory->createNamed(
                            'user_id',
                            'hidden',
                            $id,
                            ['auto_initialize' => false]
                        )
                    );
                }
            }
        );
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Chamilo\CoreBundle\Entity\JuryMembers',
            ]
        );
    }

    public function getName()
    {
        return 'jury_user';
    }
}
