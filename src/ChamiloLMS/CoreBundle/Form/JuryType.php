<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use DOctrine\ORM\EntityRepository;
use Entity;

class JuryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text');
        $builder->add('opening_date', 'datetime', array(
            'data' => new \DateTime()
            )
        );

        $builder->add('closure_date', 'datetime', array(
            'data' => new \DateTime()
            )
        );

        $builder->add('branch', 'entity', array('class'=>'Entity\BranchSync', 'property' => 'branchName', 'query_builder'=>
            function(EntityRepository $er) {
                return $er->createQueryBuilder('u')
                    //->where('u.role LIKE :role')
                    //->setParameter(':role', 'ROLE_JURY%')
                    ->orderBy('u.branchName', 'DESC');
            },)
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
            array(
                'data_class' => 'ChamiloLMS\CoreBundle\Entity\Jury'
            )
        );
    }

    public function getName()
    {
        return 'jury';
    }
}
