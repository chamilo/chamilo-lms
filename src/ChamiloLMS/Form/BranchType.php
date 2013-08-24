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
        $builderData = $builder->getData();

        $parentIdDisabled = false;
        if (!empty($builderData)) {
            $parentIdDisabled = true;
        }
        // Some shared options for plugins related form fields.
        $plugin_fields_shared_options = array('required' => false);

        $builder
            ->add('branch_name', 'text')
            ->add('branch_type', 'choice', array('choices' => array('remote_child', 'local_child', 'local_parent', 'remote_parent')))
            //->add('parent_id', 'choice', array('choices'=> array(), 'required' => false))
            ->add('parent_id', 'text', array('required' => false, 'disabled' => $parentIdDisabled))
            //->add('parent_id', 'choice', array('choices'=> array(1 => 'jjaa',2=>'ddd'), 'required' => false))
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
            ->add('last_sync_trans_id', 'text')
            ->add('access_url_id', 'text')
            // @fixme Convert to html select's when we have a way to retrive
            //        each plugin type available plugins.
            // @fixme Implement custom form options when plugins needs to store
            //        extra information on branch_sync.data.
            ->add('plugin_envelope', 'text', $plugin_fields_shared_options)
            ->add('plugin_send', 'text', $plugin_fields_shared_options)
            ->add('plugin_receive', 'text', $plugin_fields_shared_options)
            ->add('data', 'textarea', array('label' => 'Data (Unusable for now, use direct edit until UI is ready)') + $plugin_fields_shared_options)
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

