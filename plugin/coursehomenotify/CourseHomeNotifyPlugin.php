<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\CourseHomeNotify\Notification;
use Chamilo\PluginBundle\Entity\CourseHomeNotify\NotificationRelUser;
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
        $settings = [
            self::SETTING_ENABLED => 'boolean',
        ];

        parent::__construct('0.1', 'Angel Fernando Quiroz Campos', $settings);

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
     * Create table in database. And setup Doctirne entity.
     *
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    public function install()
    {
        $em = Database::getManager();

        if ($em->getConnection()->getSchemaManager()->tablesExist(['course_home_notify_notification'])) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema(
            [
                $em->getClassMetadata(Notification::class),
                $em->getClassMetadata(NotificationRelUser::class),
            ]
        );
    }

    /**
     * Uninstall process.
     * Remove Doctrine entity. And drop table in database.
     */
    public function uninstall()
    {
        $em = Database::getManager();

        if (!$em->getConnection()->getSchemaManager()->tablesExist(['course_home_notify_notification'])) {
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
        if (
            'main_bottom' !== $region
            || strpos($_SERVER['SCRIPT_NAME'], 'course_home/course_home.php') === false
        ) {
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
            ->getRepository('ChamiloPluginBundle:CourseHomeNotify\Notification')
            ->findOneBy(['course' => $course]);

        if (!$notification) {
            return '';
        }

        $modalFooter = '';
        $modalConfig = ['show' => true];

        if ($notification->getExpirationLink()) {
            /** @var NotificationRelUser $notificationUser */
            $notificationUser = $em
                ->getRepository('ChamiloPluginBundle:CourseHomeNotify\NotificationRelUser')
                ->findOneBy(['notification' => $notification, 'user' => $user]);

            if ($notificationUser) {
                return '';
            }

            $contentUrl = api_get_path(WEB_PLUGIN_PATH).$this->get_name().'/content.php?hash='.$notification->getHash();
            $link = Display::toolbarButton(
                $this->get_lang('PleaseFollowThisLink'),
                $contentUrl,
                'external-link',
                'link',
                ['id' => 'course-home-notify-link', 'target' => '_blank']
            );

            $modalConfig['keyboard'] = false;
            $modalConfig['backdrop'] = 'static';

            $modalFooter = '<div class="modal-footer">'.$link.'</div>';
        }

        $modal = '<div id="course-home-notify-modal" class="modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="'.get_lang('Close').'">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title">'.$this->get_lang('CourseNotice').'</h4>
                    </div>
                    <div class="modal-body">
                        '.$notification->getContent().'
                    </div>
                    '.$modalFooter.'
                </div>
            </div>
        </div>';

        $modal .= "<script>
            $(document).ready(function () {
                \$('#course-home-notify-modal').modal(".json_encode($modalConfig).");
                
                \$('#course-home-notify-link').on('click', function () {
                    $('#course-home-notify-modal').modal('hide');
                });
            });
        </script>";

        return $modal;
    }

    /**
     * Set the course settings.
     */
    private function setCourseSettings()
    {
        if ('true' !== $this->get(self::SETTING_ENABLED)) {
            return;
        }

        $name = $this->get_name();

        $button = Display::toolbarButton(
            $this->get_lang('SetNotification'),
            api_get_path(WEB_PLUGIN_PATH).$name.'/configure.php?'.api_get_cidreq(),
            'cog',
            'primary'
        );

        $this->course_settings = [
            [
                'name' => '<p>'.$this->get_comment().'</p>'.$button.'<hr>',
                'type' => 'html',
            ],
        ];
    }
}
