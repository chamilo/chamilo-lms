<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\CourseHomeNotify\Notification;
use Chamilo\PluginBundle\Entity\CourseHomeNotify\NotificationRelUser;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class CourseHomeNotifyPlugin.
 */
class CourseHomeNotifyPlugin extends Plugin
{
    const SETTING_ENABLED = 'enabled';

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
     */
    public function install()
    {
        $pluginEntityPath = $this->getEntityPath();

        if (!is_dir($pluginEntityPath)) {
            if (!is_writable(dirname($pluginEntityPath))) {
                $message = get_lang('ErrorCreatingDir').': '.$pluginEntityPath;
                Display::addFlash(Display::return_message($message, 'error'));

                return;
            }

            mkdir($pluginEntityPath, api_get_permissions_for_new_directories());
        }

        $fs = new Filesystem();
        $fs->mirror(__DIR__.'/Entity/', $pluginEntityPath, null, ['override']);

        $schema = Database::getManager()->getConnection()->getSchemaManager();

        if (false === $schema->tablesExist('course_home_notify_notification')) {
            $sql = "CREATE TABLE course_home_notify_notification_rel_user (id INT AUTO_INCREMENT NOT NULL, notification_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_13E723DDEF1A9D84 (notification_id), INDEX IDX_13E723DDA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB";
            Database::query($sql);

            $sql = "CREATE TABLE course_home_notify_notification (id INT AUTO_INCREMENT NOT NULL, c_id INT NOT NULL, content LONGTEXT NOT NULL, expiration_link VARCHAR(255) NOT NULL, hash VARCHAR(255) NOT NULL, INDEX IDX_7C6C1B0191D79BD3 (c_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB";
            Database::query($sql);

            $sql = "ALTER TABLE course_home_notify_notification_rel_user ADD CONSTRAINT FK_13E723DDEF1A9D84 FOREIGN KEY (notification_id) REFERENCES course_home_notify_notification (id) ON DELETE CASCADE";
            Database::query($sql);

            $sql = "ALTER TABLE course_home_notify_notification_rel_user ADD CONSTRAINT FK_13E723DDA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE";
            Database::query($sql);

            $sql = "ALTER TABLE course_home_notify_notification ADD CONSTRAINT FK_7C6C1B0191D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE";
            Database::query($sql);
        }
    }

    /**
     * @return string
     */
    public function getEntityPath()
    {
        return api_get_path(SYS_PATH).'src/Chamilo/PluginBundle/Entity/'.$this->getCamelCaseName();
    }

    /**
     * Uninstall process.
     * Remove Doctrine entity. And drop table in database.
     */
    public function uninstall()
    {
        $pluginEntityPath = $this->getEntityPath();

        $fs = new Filesystem();

        if ($fs->exists($pluginEntityPath)) {
            $fs->remove($pluginEntityPath);
        }

        $table = Database::get_main_table('course_home_notify_notification_rel_user');
        Database::query("DROP TABLE IF EXISTS $table");
        $table = Database::get_main_table('course_home_notify_notification');
        Database::query("DROP TABLE IF EXISTS $table");
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
