<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class JuryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text');
        $builder->add(
            'opening_date',
            'datetime',
            [
                'data' => new \DateTime(),
            ]
        );

        $builder->add(
            'closure_date',
            'datetime',
            [
                'data' => new \DateTime(),
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
                            ->orderBy('u.branchName', 'DESC');
                },
            ]
        );

        $builder->add('opening_user_id', 'text');
        $builder->add('closure_user_id', 'text');
        $builder->add('exercise_id', 'text');

        //$builder->add('users', 'collection', array('type' => new JuryType()));

        $builder->add('submit', 'submit');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Chamilo\CoreBundle\Entity\Jury',
            ]
        );
    }

    public function getName()
    {
        return 'jury';
    }
}
