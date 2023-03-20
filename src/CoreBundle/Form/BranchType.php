<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Chamilo\CoreBundle\Entity\BranchSync;
use DateTime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BranchType extends AbstractType
{
    /**
     * Builds the form
     * For form type details see:
     * http://symfony.com/doc/current/reference/forms/types.html.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
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
                [
                    'required' => false,
                    'disabled' => $parentIdDisabled,
                ]
            )
            //->add('parent_id', 'choice', array('choices'=> array(1 => 'jjaa',2=>'ddd'), 'required' => false))
            ->add('branch_ip', 'text')
            ->add('latitude', 'text')
            ->add('longitude', 'text')
            ->add('dwn_speed', 'text')
            ->add('up_speed', 'text')
            ->add('longitude', 'text')
            ->add('delay', 'text')
            ->add('admin_mail', EmailType::class)
            ->add('admin_name', 'text')
            ->add('admin_phone', 'text', [
                'required' => false,
            ])
            ->add(
                'last_sync_trans_date',
                'datetime',
                [
                    'data' => new DateTime(),
                ]
            )
            ->add('last_sync_type', 'text')
            ->add('last_sync_trans_id', 'text')
            ->add('ssl_pub_key', 'text')
            ->add('last_sync_trans_id', 'text')
            ->add('access_url_id', 'text')
            ->add('submit', 'submit')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => BranchSync::class,
            ]
        );
    }

    public function getName(): string
    {
        return 'branch';
    }
}
