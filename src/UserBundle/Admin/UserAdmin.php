<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\UserBundle\Admin\Model\UserAdmin as BaseUserAdmin;
use Sonata\UserBundle\Form\Type\SecurityRolesType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class UserAdmin.
 *
 * @package Chamilo\UserBundle\Admin
 */
class UserAdmin extends BaseUserAdmin
{
    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper): void
    {
        // define group zoning
        $formMapper
            ->tab('User')
            ->with('Profile', ['class' => 'col-md-6'])->end()
            ->with('General', ['class' => 'col-md-6'])->end()
            //->with('Social', array('class' => 'col-md-6'))->end()
            ->end()
            ->tab('Security')
            ->with('Status', ['class' => 'col-md-4'])->end()
            ->with('Groups', ['class' => 'col-md-4'])->end()
            ->with('Keys', ['class' => 'col-md-4'])->end()
            ->with('Roles', ['class' => 'col-md-12'])->end()
            ->end()
            ->tab('ExtraFields')
            ->with('ExtraFields', ['class' => 'col-md-4'])->end()
            ->end();

        $now = new \DateTime();

        $formMapper
            ->tab('User')
            ->with('General')
                ->add('username')
                ->add('email')
                /*->add(
                    'plainPassword',
                    'text',
                    array(
                        'required' => (!$this->getSubject() || is_null(
                                $this->getSubject()->getId()
                            )),
                    )
                )*/
            ->end()
            ->with('Profile')
                /*->add(
                    'dateOfBirth',
                    'sonata_type_date_picker',
                    array(
                        'years' => range(1900, $now->format('Y')),
                        'dp_min_date' => '1-1-1900',
                        'dp_max_date' => $now->format('c'),
                        'required' => false,
                    )
                )*/
            ->add('firstname', null, ['required' => false])
            ->add('lastname', null, ['required' => false])
//            ->add('website', 'url', ['required' => false])
            ->add('biography', TextType::class, ['required' => false])
            /*->add(
                'gender',
                'sonata_user_gender',
                array(
                    'required' => true,
                    'translation_domain' => $this->getTranslationDomain(),
                )
            )*/
            //->add('locale', 'locale', array('required' => false))
            //->add('timezone', 'timezone', array('required' => false))
            //->add('phone', null, array('required' => false))
            ->end()
            /*->with('Social')
            ->add('facebookUid', null, array('required' => false))
            ->add('facebookName', null, array('required' => false))
            ->add('twitterUid', null, array('required' => false))
            ->add('twitterName', null, array('required' => false))
            ->add('gplusUid', null, array('required' => false))
            ->add('gplusName', null, array('required' => false))
            ->end()*/
            ->end();

        //if ($this->getSubject() && !$this->getSubject()->hasRole('ROLE_SUPER_ADMIN')) {
        if ($this->getSubject()) {
            $formMapper
                ->tab('Security')
                ->with('Status')
                ->add('locked', null, ['required' => false])
                ->add('expired', null, ['required' => false])
                ->add('enabled', null, ['required' => false])
                ->add('credentialsExpired', null, ['required' => false])
                ->end()
                ->with('Groups')
                ->add(
                    'groups',
                    ModelType::class,
                    [
                        'required' => false,
                        'expanded' => true,
                        'multiple' => true,
                    ]
                )
                ->end()
                ->with('Roles')
                ->add(
                    'realRoles',
                    SecurityRolesType::class,
                    [
                        'label' => 'form.label_roles',
                        'expanded' => true,
                        'multiple' => true,
                        'required' => false,
                    ]
                )
                ->end()
                ->end();
        }

        /*$formMapper
            ->tab('Security')
            ->with('Keys')
            ->add('token', null, array('required' => false))
            ->add('twoStepVerificationCode', null, array('required' => false))
            ->end()
            ->end();*/
//
//        $formMapper
//            ->tab('ExtraFields')
//            ->with('ExtraFields')
//            ->add(
//                'extraFields',
//                'sonata_type_collection',
//                array(
//                    'cascade_validation' => true,
//                    /*'type_options' => array(
//                        // Prevents the "Delete" option from being displayed
//                        'delete' => false,
//                        'delete_options' => array(
//                            // You may otherwise choose to put the field but hide it
//                            'type'         => 'hidden',
//                            // In that case, you need to fill in the options as well
//                            'type_options' => array(
//                                'mapped'   => false,
//                                'required' => false,
//                            )
//                        )
//                    )*/
//                ),
//                array(
//                    'allow_delete' => true,
//                    'by_reference' => false,
//                    'edit' => 'inline',
//                    'inline' => 'table',
//                    'admin_code' => 'sonata.admin.user_field_values'
//                    /* 'edit' => 'inline',
//                     'inline' => 'table',
//                     'sortable' => 'position',*/
//                )
//            )
//            ->end()
//            ->end();
    }

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('username')
            ->add('firstname')
            ->add('lastname')
            ->add('email')
            ->add('officialCode')
            ->add('groups')
            ->add('active')
            //->add('registrationDate', 'doctrine_orm_datetime_range', array('input_type' => 'timestamp'))
        ;
    }
}
