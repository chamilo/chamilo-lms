<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;
use Entity;

class SessionTreeType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('type', 'choice', array('choices' => array('1', '2', '3', '4')));

        $builder->add('sessionPath', 'entity', array('class'=>'Entity\SessionPath', 'property' => 'name', 'query_builder'=>
                function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.name', 'DESC');
                },)
        );



        $builder->add('tool', 'entity', array('class'=>'Entity\Tool', 'property' => 'name', 'query_builder'=>
                function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.name', 'DESC');
                },)
        );

        $builder->add('tool', 'entity', array('class'=>'Entity\Tool', 'property' => 'name', 'query_builder'=>
                function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.name', 'DESC');
                },)
        );

        $builder->add('session', 'entity', array('class'=>'Entity\Session', 'property' => 'name', 'query_builder'=>
                function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.name', 'DESC');
                },)
        );

        $builder->add('course', 'entity', array('class'=>'Entity\Course', 'property' => 'title', 'query_builder'=>
                function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.title', 'DESC');
                },)
        );
        $builder
            ->add('submit', 'submit');

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Chamilo\CoreBundle\Entity\SessionTree'
            )
        );
    }

    public function getName()
    {
        return 'sessionPath';
    }
}

