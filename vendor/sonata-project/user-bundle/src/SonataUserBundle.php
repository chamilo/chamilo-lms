<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle;

use Sonata\CoreBundle\Form\FormHelper;
use Sonata\UserBundle\DependencyInjection\Compiler\GlobalVariablesCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SonataUserBundle extends Bundle
{
    protected $parent;

    /**
     * @param string $parent
     */
    public function __construct($parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new GlobalVariablesCompilerPass());

        $this->registerFormMapping();
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->registerFormMapping();
    }

    /**
     * Register form mapping information.
     */
    public function registerFormMapping()
    {
        FormHelper::registerFormTypeMapping([
            'fos_user_username' => 'FOS\UserBundle\Form\Type\UsernameFormType',
            'fos_user_profile' => 'FOS\UserBundle\Form\Type\ProfileFormType',
            'fos_user_registration' => 'FOS\UserBundle\Form\Type\RegistrationFormType',
            'fos_user_change_password' => 'FOS\UserBundle\Form\Type\ChangePasswordFormType',
            'fos_user_resetting' => 'FOS\UserBundle\Form\Type\ResettingFormType',
            'fos_user_group' => 'FOS\UserBundle\Form\Type\GroupFormType',
            'sonata_security_roles' => 'Sonata\UserBundle\Form\Type\SecurityRolesType',
            'sonata_user_profile' => 'Sonata\UserBundle\Form\Type\ProfileType',
            'sonata_user_gender' => 'Sonata\UserBundle\Form\Type\UserGenderListType',
            'sonata_user_registration' => 'Sonata\UserBundle\Form\Type\RegistrationFormType',
            'sonata_user_api_form_group' => 'Sonata\UserBundle\Form\Type\ApiGroupType',
            'sonata_user_api_form_user' => 'Sonata\UserBundle\Form\Type\ApiUserType',
        ]);
    }
}
