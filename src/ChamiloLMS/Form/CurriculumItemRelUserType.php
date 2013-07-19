<?php

namespace ChamiloLMS\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Entity;


class CurriculumItemRelUserType extends AbstractType
{
    public $itemId;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('description', 'text');
        $builder->add('item_id', 'hidden', array('attr' => array('value' => $this->itemId)));
        //$builder->add('id', 'hidden');
        //$builder->add('user_id', 'hidden');
        //$builder->add('order_id', 'hidden');
        //$builder->add('submit', 'submit');

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Entity\CurriculumItemRelUser'
            )
        );
    }

    public function getName()
    {
        return 'curriculumItemRelUser';
    }

    public function __construct($itemId = null)
    {
        $this->itemId = $itemId;
    }
}
