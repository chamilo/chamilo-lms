<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Manager\CourseManager;
use Chamilo\CoreBundle\Entity\Repository\CourseRepository;
use Chamilo\CourseBundle\Tool\BaseTool;
use Chamilo\CourseBundle\ToolChain;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Chamilo\SettingsBundle\Transformer\ArrayToIdentifierTransformer;

/**
 * Class CasSettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class CasSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(
                array(
                    'cas_activate' => '',
                    'cas_server' => '',
                    'cas_server_uri' => '',
                    'cas_port' => '',
                    'cas_protocol' => '',
                    'cas_add_user_activate' => '',
                    'update_user_info_cas_with_ldap' => '',
                )
            )
//            ->setAllowedTypes(
//                array(
//                )
//            )
        ;

    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('cas_activate', YesNoType::class)
            ->add('cas_server')
            ->add('cas_server_uri')
            ->add('cas_port')
            ->add('cas_protocol')
            ->add('cas_server')
            ->add('cas_add_user_activate')
            ->add('update_user_info_cas_with_ldap', YesNoType::class)
        ;
    }
}
