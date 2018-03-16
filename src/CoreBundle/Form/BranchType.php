<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BranchType extends AbstractType
{
    /**
     * Builds the form
     * For form type details see:
     * http://symfony.com/doc/current/reference/forms/types.html.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builderData = $builder->getData();

        $parentIdDisabled = false;
        if (!empty($builderData)) {
            $parentIdDisabled = true;
        }

        $builder
            ->add('branch_name', 'text')
            ->add(
                'branch_type',
                'choice',
                [
                    'choices' => [
                        'remote_child',
                        'local_child',
                        'local_parent',
                        'remote_parent',
                    ],
                ]
            )
            //->add('parent_id', 'choice', array('choices'=> array(), 'required' => false))
            ->add(
                'parent_id',
                'text',
                ['required' => false, 'disabled' => $parentIdDisabled]
            )
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
            ->add('admin_phone', 'text', ['required' => false])
            ->add(
                'last_sync_trans_date',
                'datetime',
                [
                    'data' => new \DateTime(),
                ]
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
            [
                'data_class' => 'Chamilo\CoreBundle\Entity\BranchSync',
            ]
        );
    }

    public function getName()
    {
        return 'branch';
    }
}
