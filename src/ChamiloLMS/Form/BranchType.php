<?php

namespace ChamiloLMS\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Entity;

class BranchType extends AbstractType
{
    /**
     * Builds the form
     * For form type details see:
     * http://symfony.com/doc/current/reference/forms/types.html
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('branch_name', 'text')
            ->add('parent_id', 'choice', array('required' => false))
            ->add('branch_ip', 'text')
            ->add('latitude', 'text')
            ->add('longitude', 'text')
            ->add('dwn_speed', 'text')
            ->add('up_speed', 'text')
            ->add('longitude', 'text')
            ->add('delay', 'text')
            ->add('admin_mail', 'email')
            ->add('admin_name', 'text')
            ->add('admin_phone', 'text', array('required' => false))
            ->add('last_sync_trans_date', 'datetime', array(
                    'data' => new \DateTime()
                    )
            )
            ->add('last_sync_type', 'text')
            ->add('last_sync_trans_id', 'text')
            ->add('ssl_pub_key', 'text')
            ->add('last_sync_trans_id', 'text')
            ->add('access_url_id', 'text')
            ->add('submit', 'submit');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Entity\BranchSync'
            )
        );
    }

    public function getName()
    {
        return 'branch';
    }
}

