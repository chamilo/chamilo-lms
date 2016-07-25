<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Admin\Model;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\UserBundle\Model\UserInterface;

use FOS\UserBundle\Model\UserManagerInterface;

class UserAdmin extends Admin
{

    /**
     * {@inheritdoc}
     */
    public function getFormBuilder()
    {
        $this->formOptions['data_class'] = $this->getClass();

        $options = $this->formOptions;
        $options['validation_groups'] = (!$this->getSubject() || is_null($this->getSubject()->getId())) ? 'Registration' : 'Profile';

        $formBuilder = $this->getFormContractor()->getFormBuilder( $this->getUniqid(), $options);

        $this->defineFormBuilder($formBuilder);

        return $formBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getExportFields()
    {
        // avoid security field to be exported
        return array_filter(parent::getExportFields(), function($v) {
            return !in_array($v, array('password', 'salt'));
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('username')
            ->add('email')
            ->add('groups')
            ->add('enabled', null, array('editable' => true))
            ->add('locked', null, array('editable' => true))
            ->add('createdAt')
        ;

        if ($this->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            $listMapper
                ->add('impersonating', 'string', array('template' => 'SonataUserBundle:Admin:Field/impersonating.html.twig'))
            ;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $filterMapper)
    {
        $filterMapper
            ->add('id')
            ->add('username')
            ->add('locked')
            ->add('email')
            ->add('groups')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->with('General')
                ->add('username')
                ->add('email')
            ->end()
            ->with('Groups')
                ->add('groups')
            ->end()
            ->with('Profile')
                ->add('dateOfBirth')
                ->add('firstname')
                ->add('lastname')
                ->add('website')
                ->add('biography')
                ->add('gender')
                ->add('locale')
                ->add('timezone')
                ->add('phone')
            ->end()
            ->with('Social')
                ->add('facebookUid')
                ->add('facebookName')
                ->add('twitterUid')
                ->add('twitterName')
                ->add('gplusUid')
                ->add('gplusName')
            ->end()
            ->with('Security')
                ->add('token')
                ->add('twoStepVerificationCode')
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('General')
                ->add('username')
                ->add('email')
                ->add('plainPassword', 'text', array(
                    'required' => (!$this->getSubject() || is_null($this->getSubject()->getId()))
                ))
            ->end()
            ->with('Groups')
                ->add('groups', 'sonata_type_model', array(
                    'required' => false,
                    'expanded' => true,
                    'multiple' => true
                ))
            ->end()
            ->with('Profile')
                ->add('dateOfBirth', 'birthday', array('required' => false))
                ->add('firstname', null, array('required' => false))
                ->add('lastname', null, array('required' => false))
                ->add('website', 'url', array('required' => false))
                ->add('biography', 'text', array('required' => false))
                ->add('gender', 'sonata_user_gender', array(
                    'required' => true,
                    'translation_domain' => $this->getTranslationDomain()
                ))
                ->add('locale', 'locale', array('required' => false))
                ->add('timezone', 'timezone', array('required' => false))
                ->add('phone', null, array('required' => false))
            ->end()
            ->with('Social')
                ->add('facebookUid', null, array('required' => false))
                ->add('facebookName', null, array('required' => false))
                ->add('twitterUid', null, array('required' => false))
                ->add('twitterName', null, array('required' => false))
                ->add('gplusUid', null, array('required' => false))
                ->add('gplusName', null, array('required' => false))
            ->end()
        ;

        if ($this->getSubject() && !$this->getSubject()->hasRole('ROLE_SUPER_ADMIN')) {
            $formMapper
                ->with('Management')
                    ->add('realRoles', 'sonata_security_roles', array(
                        'label'    => 'form.label_roles',
                        'expanded' => true,
                        'multiple' => true,
                        'required' => false
                    ))
                    ->add('locked', null, array('required' => false))
                    ->add('expired', null, array('required' => false))
                    ->add('enabled', null, array('required' => false))
                    ->add('credentialsExpired', null, array('required' => false))
                ->end()
            ;
        }

        $formMapper
            ->with('Security')
                ->add('token', null, array('required' => false))
                ->add('twoStepVerificationCode', null, array('required' => false))
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate($user)
    {
        $this->getUserManager()->updateCanonicalFields($user);
        $this->getUserManager()->updatePassword($user);
    }

    /**
     * @param UserManagerInterface $userManager
     */
    public function setUserManager(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @return UserManagerInterface
     */
    public function getUserManager()
    {
        return $this->userManager;
    }
}
