<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SessionTreeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'type',
            'choice',
            ['choices' => ['1', '2', '3', '4']]
        );

        $builder->add(
            'sessionPath',
            'entity',
            [
                'class' => 'Entity\SessionPath',
                'property' => 'name',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                            ->orderBy('u.name', 'DESC');
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
                            ->orderBy('u.name', 'DESC');
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
                            ->orderBy('u.name', 'DESC');
                },
            ]
        );

        $builder->add(
            'session',
            'entity',
            [
                'class' => 'Entity\Session',
                'property' => 'name',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                            ->orderBy('u.name', 'DESC');
                },
            ]
        );

        $builder->add(
            'course',
            'entity',
            [
                'class' => 'Entity\Course',
                'property' => 'title',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                            ->orderBy('u.title', 'DESC');
                },
            ]
        );
        $builder
            ->add('submit', 'submit');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Chamilo\CoreBundle\Entity\SessionTree',
            ]
        );
    }

    public function getName()
    {
        return 'sessionPath';
    }
}
