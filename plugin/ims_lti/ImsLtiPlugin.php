<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\UserBundle\Entity\User;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Filesystem\Filesystem;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

/**
 * Description of MsiLti
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class ImsLtiPlugin extends Plugin
{
    const TABLE_TOOL = 'plugin_ims_lti_tool';

    public $isAdminPlugin = true;

    /**
     * Class constructor
     */
    protected function __construct()
    {
        $version = '1.1 (beta)';
        $author = 'Angel Fernando Quiroz Campos';

        parent::__construct($version, $author, ['enabled' => 'boolean']);

        $this->setCourseSettings();
    }

    /**
     * Get the class instance
     * @staticvar MsiLtiPlugin $result
     * @return ImsLtiPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    /**
     * Get the plugin directory name
     */
    public function get_name()
    {
        return 'ims_lti';
    }

    /**
     * Install the plugin. Setup the database
     */
    public function install()
    {
        $pluginEntityPath = $this->getEntityPath();

        if (!is_dir($pluginEntityPath)) {
            if (!is_writable(dirname($pluginEntityPath))) {
                $message = get_lang('ErrorCreatingDir').': '.$pluginEntityPath;
                Display::addFlash(Display::return_message($message, 'error'));

                return false;
            }

            mkdir($pluginEntityPath, api_get_permissions_for_new_directories());
        }

        $fs = new Filesystem();
        $fs->mirror(__DIR__.'/Entity/', $pluginEntityPath, null, ['override']);

        $this->createPluginTables();
    }

    /**
     * Unistall plugin. Clear the database
     */
    public function uninstall()
    {
        $pluginEntityPath = $this->getEntityPath();
        $fs = new Filesystem();

        if ($fs->exists($pluginEntityPath)) {
            $fs->remove($pluginEntityPath);
        }

        try {
            $this->dropPluginTables();
            $this->removeTools();
        } catch (DBALException $e) {
            error_log('Error while uninstalling IMS/LTI plugin: '.$e->getMessage());
        }
    }

    /**
     * Creates the plugin tables on database
     *
     * @return boolean
     */
    private function createPluginTables()
    {
        $entityManager = Database::getManager();
        $connection = $entityManager->getConnection();
        $pluginSchema = new Schema();
        $platform = $connection->getDatabasePlatform();
        if (!$connection->getSchemaManager()->tablesExist(self::TABLE_TOOL)) {
            $toolTable = $pluginSchema->createTable(self::TABLE_TOOL);
            $toolTable->addColumn(
                'id',
                \Doctrine\DBAL\Types\Type::INTEGER,
                ['autoincrement' => true, 'unsigned' => true]
            );
            $toolTable->addColumn('name', Type::STRING);
            $toolTable->addColumn('description', Type::TEXT)->setNotnull(false);
            $toolTable->addColumn('launch_url', Type::TEXT);
            $toolTable->addColumn('consumer_key', Type::STRING);
            $toolTable->addColumn('shared_secret', Type::STRING);
            $toolTable->addColumn('custom_params', Type::TEXT)->setNotnull(false);
            $toolTable->addColumn('is_global', Type::BOOLEAN);
            $toolTable->setPrimaryKey(['id']);

            $queries = $pluginSchema->toSql($platform);

            foreach ($queries as $query) {
                Database::query($query);
            }
        }


        return true;
    }

    /**
     * Drops the plugin tables on database
     *
     * @return boolean
     */
    private function dropPluginTables()
    {
        $entityManager = Database::getManager();
        $connection = $entityManager->getConnection();
        $chamiloSchema = $connection->getSchemaManager();

        if (!$chamiloSchema->tablesExist([self::TABLE_TOOL])) {
            return false;
        }

        $sql = 'DROP TABLE IF EXISTS '.self::TABLE_TOOL;
        Database::query($sql);

        return true;
    }

    /**
     *
     */
    private function removeTools()
    {
        $sql = "DELETE FROM c_tool WHERE link LIKE 'ims_lti/start.php%' AND category = 'plugin'";
        Database::query($sql);
    }

    /**
     * Set the course settings
     */
    private function setCourseSettings()
    {
        $button = Display::toolbarButton(
            $this->get_lang('ConfigureExternalTool'),
            api_get_path(WEB_PLUGIN_PATH).'ims_lti/add.php?'.api_get_cidreq(),
            'cog',
            'primary'
        );

        $this->course_settings = [
            [
                'name' => $this->get_lang('ImsLtiDescription').$button.'<hr>',
                'type' => 'html',
            ],
        ];
    }

    /**
     * Add the course tool
     * @param Course $course
     * @param ImsLtiTool $tool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addCourseTool(Course $course, ImsLtiTool $tool)
    {
        $em = Database::getManager();
        $cTool = new CTool();
        $cTool
            ->setCourse($course)
            ->setName($tool->getName())
            ->setLink($this->get_name().'/start.php?'.http_build_query(['id' => $tool->getId()]))
            ->setImage($this->get_name().'.png')
            ->setVisibility(1)
            ->setAdmin(0)
            ->setAddress('squaregray.gif')
            ->setAddedTool('NO')
            ->setTarget('_self')
            ->setCategory('plugin')
            ->setSessionId(0);

        $em->persist($cTool);
        $em->flush();

        $cTool->setId($cTool->getIid());

        $em->persist($cTool);
        $em->flush();
    }

    /**
     * @return string
     */
    protected function getConfigExtraText()
    {
        $text = $this->get_lang('ImsLtiDescription');
        $text .= sprintf(
            $this->get_lang('ManageToolButton'),
            api_get_path(WEB_PLUGIN_PATH).'ims_lti/admin.php'
        );

        return $text;
    }

    /**
     * @return string
     */
    public function getEntityPath()
    {
        return api_get_path(SYS_PATH).'src/Chamilo/PluginBundle/Entity/'.$this->getCamelCaseName();
    }

    public static function isInstructor()
    {
        api_is_allowed_to_edit(false, true);
    }

    /**
     * @param User         $user
     *
     * @return string
     */
    public static function getUserRoles(User $user)
    {
        if ($user->getStatus() === INVITEE) {
            return 'Learner/GuestLearner,Learner';
        }

        if (!api_is_allowed_to_edit(false, true)) {
            return 'Learner,Learner/Learner';
        }

        $roles = ['Instructor'];

        if (api_is_platform_admin_by_id($user->getId())) {
            $roles[] = 'Administrator/SystemAdministrator';
        }

        return implode(',', $roles);
    }

    /**
     * @param int $userId
     *
     * @return string
     */
    public static function generateToolUserId($userId)
    {
        $siteName = api_get_setting('siteName');
        $institution = api_get_setting('Institution');
        $toolUserId = "$siteName - $institution - $userId";
        $toolUserId = api_replace_dangerous_char($toolUserId);

        return $toolUserId;
    }

    /**
     * @param Course       $course
     * @param Session|null $session
     *
     * @return string
     */
    public static function getRoleScopeMentor(Course $course, Session $session = null)
    {
        $scope = [];

        if ($session) {
            $students = $session->getUserCourseSubscriptionsByStatus($course, Session::STUDENT);
        } else {
            $students = $course->getStudents();
        }

        /** @var SessionRelCourseRelUser|CourseRelUser $subscription */
        foreach ($students as $subscription) {
            $scope[] = self::generateToolUserId($subscription->getUser()->getId());
        }

        return implode(',', $scope);
    }
}
