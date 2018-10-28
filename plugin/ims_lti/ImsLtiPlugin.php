<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Chamilo\UserBundle\Entity\User;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\Filesystem\Filesystem;

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
            $toolTable->addColumn('active_deep_linking', Type::BOOLEAN)->setDefault(false);
            $toolTable->addColumn('c_id', Type::INTEGER)->setNotnull(false);
            $toolTable->addColumn('gradebook_eval_id', Type::INTEGER)->setNotnull(false);
            $toolTable->addForeignKeyConstraint(
                'course',
                ['c_id'],
                ['id'],
                [],
                'FK_C5E47F7C91D79BD3'
            );
            $toolTable->addForeignKeyConstraint(
                'gradebook_evaluation',
                ['gradebook_eval_id'],
                ['id'],
                ['onDelete' => 'SET NULL'],
                'FK_C5E47F7C82F80D8B'
            );
            $toolTable->setPrimaryKey(['id']);
            $toolTable->addIndex(['c_id'], 'IDX_C5E47F7C91D79BD3');
            $toolTable->addIndex(['gradebook_eval_id'], 'IDX_C5E47F7C82F80D8B');

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
            api_get_path(WEB_PLUGIN_PATH).'ims_lti/configure.php?'.api_get_cidreq(),
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
     * @param Course     $course
     * @param ImsLtiTool $ltiTool
     *
     * @return CTool
     */
    public function findCourseToolByLink(Course $course, ImsLtiTool $ltiTool)
    {
        $em = Database::getManager();
        $toolRepo = $em->getRepository('ChamiloCourseBundle:CTool');

        /** @var CTool $cTool */
        $cTool = $toolRepo->findOneBy(
            [
                'cId' => $course,
                'link' => self::generateToolLink($ltiTool),
            ]
        );

        return $cTool;
    }

    /**
     * @param CTool      $courseTool
     * @param ImsLtiTool $ltiTool
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateCourseTool(CTool $courseTool, ImsLtiTool $ltiTool)
    {
        $em = Database::getManager();

        $courseTool->setName($ltiTool->getName());

        $em->persist($courseTool);
        $em->flush();
    }

    /**
     * @param ImsLtiTool $tool
     *
     * @return string
     */
    private static function generateToolLink(ImsLtiTool $tool)
    {
        return  'ims_lti/start.php?id='.$tool->getId();
    }

    /**
     * Add the course tool
     *
     * @param Course     $course
     * @param ImsLtiTool $tool
     */
    public function addCourseTool(Course $course, ImsLtiTool $tool)
    {
        $this->createLinkToCourseTool(
            $tool->getName(),
            $course->getId(),
            null,
            self::generateToolLink($tool)
        );
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

    /**
     * @param array      $contentItem
     * @param ImsLtiTool $baseLtiTool
     * @param Course     $course
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveItemAsLtiLink(array $contentItem, ImsLtiTool $baseLtiTool, Course $course)
    {
        $em = Database::getManager();
        $ltiToolRepo = $em->getRepository('ChamiloPluginBundle:ImsLti\ImsLtiTool');

        $url = empty($contentItem['url']) ? $baseLtiTool->getLaunchUrl() : $contentItem['url'];

        /** @var ImsLtiTool $newLtiTool */
        $newLtiTool = $ltiToolRepo->findOneBy(['launchUrl' => $url, 'isGlobal' => false, 'course' => $course]);

        if (empty($newLtiTool)) {
            $newLtiTool = new ImsLtiTool();
            $newLtiTool
                ->setLaunchUrl($url)
                ->setConsumerKey(
                    $baseLtiTool->getConsumerKey()
                )
                ->setSharedSecret(
                    $baseLtiTool->getSharedSecret()
                );
        }

        $newLtiTool
            ->setName(
                !empty($contentItem['title']) ? $contentItem['title'] : $baseLtiTool->getName()
            )
            ->setDescription(
                !empty($contentItem['text']) ? $contentItem['text'] : null
            )
            ->setCourse($course);

        $em->persist($newLtiTool);
        $em->flush();

        $courseTool = $this->findCourseToolByLink($course, $newLtiTool);

        if ($courseTool) {
            $this->updateCourseTool($courseTool, $newLtiTool);

            return;
        }

        $this->addCourseTool($course, $newLtiTool);
    }

    /**
     * @return null|SimpleXMLElement
     */
    private function getRequestXmlElement()
    {
        $request = file_get_contents("php://input");

        if (empty($request)) {
            return null;
        }

        $xml = new SimpleXMLElement($request);

        return $xml;
    }

    /**
     * @return ImsLtiServiceResponse|null
     */
    public function processServiceRequest()
    {
        $xml = $this->getRequestXmlElement();

        if (empty($xml)) {
            return null;
        }

        $request = ImsLtiServiceRequestFactory::create($xml);
        $response = $request->process();

        return $response;
    }

    /**
     * @param int    $toolId
     * @param Course $course
     *
     * @return bool
     */
    public static function existsToolInCourse($toolId, Course $course)
    {
        $em = Database::getManager();
        $toolRepo = $em->getRepository('ChamiloPluginBundle:ImsLti\ImsLtiTool');

        /** @var ImsLtiTool $tool */
        $tool = $toolRepo->findOneBy(['id' => $toolId, 'isGlobal' => false, 'course' => $course]);

        return !empty($tool);
    }
}
