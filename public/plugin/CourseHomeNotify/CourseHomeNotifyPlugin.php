<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\PluginBundle\CourseHomeNotify\Entity\Notification;
use Chamilo\PluginBundle\CourseHomeNotify\Entity\NotificationRelUser;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Class CourseHomeNotifyPlugin.
 */
class CourseHomeNotifyPlugin extends Plugin
{
    public const SETTING_ENABLED = 'enabled';

    /**
     * CourseHomeNotifyPlugin constructor.
     */
    protected function __construct()
    {
        $settings = [];

        parent::__construct('0.1', 'Angel Fernando Quiroz Campos', $settings);

        $this->isAdminPlugin = true;
        $this->isCoursePlugin = true;
        $this->addCourseTool = false;
        $this->setCourseSettings();
    }

    /**
     * @return CourseHomeNotifyPlugin|null
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Install process.
     * Create table in database. And setup Doctrine entity.
     *
     * @throws \Doctrine\ORM\Tools\ToolsException
     * @throws \Doctrine\DBAL\Exception
     */
    public function install()
    {
        $em = Database::getManager();
        $schemaManager = $em->getConnection()->createSchemaManager();

        if (!$schemaManager->tablesExist(['course_home_notify_notification'])) {
            $schemaTool = new SchemaTool($em);
            $schemaTool->createSchema(
                [
                    $em->getClassMetadata(Notification::class),
                    $em->getClassMetadata(NotificationRelUser::class),
                ]
            );
        }
    }

    /**
     * Uninstall process.
     * Remove Doctrine entity. And drop table in database.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function uninstall()
    {
        $em = Database::getManager();

        if (!$em->getConnection()->createSchemaManager()->tablesExist(['course_home_notify_notification'])) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema(
            [
                $em->getClassMetadata(Notification::class),
                $em->getClassMetadata(NotificationRelUser::class),
            ]
        );
    }

    /**
     * @param string $region
     *
     * @return string
     */
    public function renderRegion($region)
    {
        $routeName = Container::getRequest()->query->get('_route_name');

        if (
            'content_bottom' !== $region || $routeName !== 'CourseHome'
        ) {
            return '';
        }

        if (!$this->isEnabled()) {
            return '';
        }

        $courseId = api_get_course_int_id();
        $userId = api_get_user_id();

        if (empty($courseId) || empty($userId)) {
            return '';
        }

        $course = api_get_course_entity($courseId);
        $user = api_get_user_entity($userId);

        $em = Database::getManager();
        /** @var Notification $notification */
        $notification = $em
            ->getRepository(Notification::class)
            ->findOneBy(['course' => $course]);

        if (!$notification) {
            return '';
        }

        $footerJs = '';
        $preventCloseJs = '';

        if ($notification->getExpirationLink()) {
            /** @var NotificationRelUser $notificationUser */
            $notificationUser = $em
                ->getRepository(NotificationRelUser::class)
                ->findOneBy(['notification' => $notification, 'user' => $user]);

            if ($notificationUser) {
                return '';
            }

            $contentUrl = api_get_path(WEB_PLUGIN_PATH)
                .$this->get_name()
                .'/content.php?hash='
                .urlencode($notification->getHash())
                .'&'
                .api_get_cidreq();

            $link = Display::toolbarButton(
                $this->get_lang('PleaseFollowThisLink'),
                $contentUrl,
                'external-link',
                'link',
                ['id' => 'course-home-notify-link', 'target' => '_blank']
            );

            $footerJs = "footer.html(".json_encode($link).");\n"
                ."\$('#course-home-notify-link').on('click', function () {\n"
                ."dialog.close();\n"
                ."});";

            // Equivalent to the old keyboard:false / backdrop:static config:
            // prevent dismissing with the Escape key so the user follows the link.
            $preventCloseJs = "dialog.addEventListener('cancel', function (e) {\n"
                ."e.preventDefault();\n"
                ."});";
        }

        // The notice is authored with a rich-text editor and rendered through
        // jQuery .html(), so sanitize it to strip scripts, inline event handlers
        // and javascript: URLs while keeping legitimate formatting (stored XSS).
        $content = Security::remove_XSS($notification->getContent());

        return "<script>
            \$(function () {
                var dialog = document.getElementById('global-modal');

                if (!dialog) {
                    return;
                }

                \$('#global-modal-title').text(".json_encode($this->get_lang('CourseNotice')).");
                \$('#global-modal-body').html(".json_encode($content).");

                var footer = \$('#global-modal .legacy-modal__footer');
                footer.empty();
                $footerJs

                $preventCloseJs

                dialog.addEventListener('close', function () {
                    footer.empty();
                });

                if (!dialog.open) {
                    dialog.showModal();
                }
            });
        </script>";
    }

    /**
     * Set the course settings.
     */
    private function setCourseSettings()
    {
        $name = $this->get_name();

        $button = Display::toolbarButton(
            $this->get_lang('SetNotification'),
            api_get_path(WEB_PLUGIN_PATH).$name.'/configure.php?'.api_get_cidreq(),
            'cog',
            'primary'
        );

        $this->course_settings = [
            [
                'name' => 'notification_configuration',
                'group' => 'course_home_notify',
                'type' => 'html',
                'init_value' => '<p>'.$this->get_comment().'</p>'.$button.'<hr>',
            ],
        ];
    }
}
