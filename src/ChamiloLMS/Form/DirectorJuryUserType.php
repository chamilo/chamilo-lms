<?php

namespace ChamiloLMS\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Entity;
use Doctrine\ORM\EntityRepository;

class DirectorJuryUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('username', 'text');
        $builder->add('firstname', 'text');
        $builder->add('lastname', 'text');
        $builder->add('password', 'repeated', array(
           'first_name' => 'password',
           'second_name' => 'confirm',
           'type' => 'password'
        ));

        $builder->add('roles', 'entity', array( 'class'=>'Entity\Role', 'property' => 'name', 'query_builder'=>
            function (EntityRepository $er) {
                return $er->createQueryBuilder('u')
                    ->where('u.role LIKE :role1 OR u.role LIKE :role2')
                    ->setParameter(':role1', 'ROLE_JURY_MEMBER')
                    ->setParameter(':role2', 'ROLE_JURY_SUBSTITUTE')
                    ->orderBy('u.name', 'DESC');
            },)
        );

        $builder->add('submit', 'submit');

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Entity\User'
            )
        );
    }

    public function getName()
    {
        return 'user';
    }
}
