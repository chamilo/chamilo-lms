<?php
/* For license terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CoreBundle\Entity\Course;
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
        $version = '1.0 (beta)';
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
     * @return boolean
     * @throws \Doctrine\DBAL\DBALException
     */
    private function createPluginTables()
    {
        $entityManager = Database::getManager();
        $connection = $entityManager->getConnection();
        $pluginSchema = new Schema();
        $platform = $connection->getDatabasePlatform();

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

        return true;
    }

    /**
     * Drops the plugin tables on database
     * @return boolean
     * @throws \Doctrine\DBAL\DBALException
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
     * @throws \Doctrine\DBAL\DBALException
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
            $this->get_lang('AddExternalTool'),
            api_get_path(WEB_PLUGIN_PATH).'ims_lti/add.php?'.api_get_cidreq(),
            'cog',
            'primary'
        );

        $this->course_settings = [
            [
                'name' => $this->get_lang('ImsLtiDescription').$button.'<hr>',
                'type' => 'html'
            ]
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
            ->setCId($course->getId())
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
}
