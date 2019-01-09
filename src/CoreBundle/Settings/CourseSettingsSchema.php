<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Manager\CourseManager;
use Chamilo\CoreBundle\Form\Type\YesNoType;
use Chamilo\CourseBundle\Tool\BaseTool;
use Chamilo\CourseBundle\ToolChain;
use Chamilo\SettingsBundle\Transformer\ArrayToIdentifierTransformer;
use Sylius\Bundle\ResourceBundle\Form\DataTransformer\ResourceToIdentifierTransformer;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class CourseSettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class CourseSettingsSchema extends AbstractSettingsSchema
{
    /**
     * @var ToolChain
     */
    protected $toolChain;

    /**
     * @var CourseManager
     */
    protected $courseManager;

    /**
     * @param ToolChain $tools
     */
    public function setToolChain(ToolChain $tools)
    {
        $this->toolChain = $tools;
    }

    /**
     * @param CourseManager $manager
     */
    public function setCourseManager($manager)
    {
        $this->courseManager = $manager;
    }

    /**
     * @return array
     */
    public function getProcessedToolChain()
    {
        $tools = [];
        /** @var BaseTool $tool */
        foreach ($this->toolChain->getTools() as $tool) {
            $name = $tool->getName();
            $tools[$name] = $name;
        }

        return $tools;
    }

    /**
     * {@inheritdoc}
     */
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $tools = $this->getProcessedToolChain();
        $em = $this->courseManager->getEntityManager();

        $builder
            ->setDefaults(
                [
                    'homepage_view' => 'activity_big',
                    'show_tool_shortcuts' => 'false', // @todo check default value?
                    'active_tools_on_create' => $tools,
                    'display_coursecode_in_courselist' => 'false',
                    'display_teacher_in_courselist' => 'true',
                    'student_view_enabled' => 'true',
                    'go_to_course_after_login' => 'false',
                    'show_navigation_menu' => 'false',
                    'enable_tool_introduction' => 'false',
                    'breadcrumbs_course_homepage' => 'course_title',
                    'example_material_course_creation' => 'true',
                    'allow_course_theme' => 'true',
                    'allow_users_to_create_courses' => 'true',
                    'show_courses_descriptions_in_catalog' => 'true',
                    'send_email_to_admin_when_create_course' => 'false',
                    'allow_user_course_subscription_by_course_admin' => 'true',
                    'course_validation' => 'false',
                    'course_validation_terms_and_conditions_url' => '',
                    'course_hide_tools' => [],
                    'scorm_cumulative_session_time' => 'true',
                    'courses_default_creation_visibility' => '2',
                    //COURSE_VISIBILITY_OPEN_PLATFORM
                    'allow_public_certificates' => 'false',
                    'allow_lp_return_link' => 'true',
                    'course_creation_use_template' => null,
                    'hide_scorm_export_link' => 'false',
                    'hide_scorm_copy_link' => 'false',
                    'hide_scorm_pdf_link' => 'true',
                    'course_catalog_published' => 'false',
                    'course_images_in_courses_list' => 'true',
                    'teacher_can_select_course_template' => 'true',
                    'show_toolshortcuts' => '',
                    'enable_record_audio' => 'false',
                    'lp_show_reduced_report' => 'false',
                    'course_creation_splash_screen' => 'true',
                    'block_registered_users_access_to_open_course_contents' => 'false', // @todo
                ]
            )
            ->setTransformer(
                'active_tools_on_create',
                new ArrayToIdentifierTransformer()
            )
            ->setTransformer(
                'course_hide_tools',
                new ArrayToIdentifierTransformer()
            )
            ->setTransformer(
                'course_creation_use_template',
                new ResourceToIdentifierTransformer($em->getRepository('ChamiloCoreBundle:Course'))
            )
        ;

        $allowedTypes = [
            'homepage_view' => ['string'],
            'active_tools_on_create' => ['array'],
            'course_hide_tools' => ['array'],
            'display_coursecode_in_courselist' => ['string'],
            'display_teacher_in_courselist' => ['string'],
            'student_view_enabled' => ['string'],
        ];

        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $tools = $this->getProcessedToolChain();

        $builder
            ->add(
                'homepage_view',
                ChoiceType::class,
                [
                    'choices' => [
                        'HomepageView2column' => '2column',
                        'HomepageView3column' => '3column',
                        'HomepageViewVerticalActivity' => 'vertical_activity',
                        'HomepageViewActivity' => 'activity',
                        'HomepageViewActivityBig' => 'activity_big',
                    ],
                ]
            )
            ->add('show_tool_shortcuts', YesNoType::class)
            ->add(
                'active_tools_on_create',
                ChoiceType::class,
                [
                    'choices' => $tools,
                    'multiple' => true,
                    'expanded' => true,
                ]
            )
            ->add('display_coursecode_in_courselist', YesNoType::class)
            ->add('display_teacher_in_courselist', YesNoType::class)
            ->add('student_view_enabled', YesNoType::class)
            ->add('go_to_course_after_login', YesNoType::class)
            ->add(
                'show_navigation_menu',
                ChoiceType::class,
                [
                    'choices' => [
                        'No' => 'false',
                        'IconsOnly' => 'icons',
                        'TextOnly' => 'text',
                        'IconsText' => 'iconstext',
                    ],
                ]
            )
            ->add('enable_tool_introduction', YesNoType::class)
            ->add(
                'breadcrumbs_course_homepage',
                ChoiceType::class,
                [
                    'choices' => [
                        'CourseHomepage' => 'course_home',
                        'CourseCode' => 'course_code',
                        'CourseTitle' => 'course_title',
                        'SessionNameAndCourseTitle' => 'session_name_and_course_title',
                    ],
                ]
            )
            ->add('example_material_course_creation', YesNoType::class)
            ->add('allow_course_theme', YesNoType::class)
            ->add('allow_users_to_create_courses', YesNoType::class)
            ->add('show_courses_descriptions_in_catalog', YesNoType::class)
            ->add('send_email_to_admin_when_create_course', YesNoType::class)
            ->add('allow_user_course_subscription_by_course_admin', YesNoType::class)
            ->add('course_validation', YesNoType::class)
            ->add('course_validation_terms_and_conditions_url', UrlType::class)
            ->add(
                'course_hide_tools',
                ChoiceType::class,
                [
                    'choices' => $tools,
                    'multiple' => true,
                    'expanded' => true,
                ]
            )
            ->add('scorm_cumulative_session_time', YesNoType::class)
            ->add(
                'courses_default_creation_visibility',
                ChoiceType::class,
                [
                    'choices' => [
                        'Public' => '3',
                        'Open' => '2',
                        'Private' => '1',
                        'Closed' => '0',
                    ],
                ]
            )
            ->add('allow_public_certificates', YesNoType::class)
            ->add('allow_lp_return_link', YesNoType::class)
            ->add(
                'course_creation_use_template',
                EntityType::class,
                [
                    'class' => Course::class,
                    'placeholder' => 'Choose ...',
                    'empty_data' => null,
                    'data' => null,
                ]
            )
            ->add('hide_scorm_export_link', YesNoType::class)
            ->add('hide_scorm_copy_link', YesNoType::class)
            ->add('hide_scorm_pdf_link', YesNoType::class)
            ->add('course_catalog_published', YesNoType::class)
            ->add('course_images_in_courses_list', YesNoType::class)
            ->add('teacher_can_select_course_template', YesNoType::class)
            ->add('show_toolshortcuts', YesNoType::class)
            ->add('enable_record_audio', YesNoType::class)
            ->add('lp_show_reduced_report', YesNoType::class)
            ->add('course_creation_splash_screen', YesNoType::class)
            ->add('block_registered_users_access_to_open_course_contents', YesNoType::class)
        ;
    }
}
