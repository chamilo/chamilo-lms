<?php
/* For license terms, see /license.txt */
/**
 * Description of MsiLti
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class ImsLtiPlugin extends Plugin
{
    const TABLE_TOOL = 'plugin_msi_lti_tool';

    /**
     * Class cronstructor
     */
    protected function __construct()
    {
        $version = '1.0';
        $author = 'Angel Fernando Quiroz Campos';

        parent::__construct($version, $author, ['enabled' => 'boolean']);

        $this->setCourseSettings();
    }

    /**
     * Get the class instance
     * @staticvar MsiLtiPlugin $result
     * @return MsiLtiPlugin
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
        $this->setupDatabase();
    }

    /**
     * Unistall plugin. Clear the database
     */
    public function uninstall()
    {
        $this->clearDatabase();
    }

    /**
     * Creates the plugin tables on database
     * @return boolean
     */
    private function setupDatabase()
    {
        $entityManager = Database::getManager();
        $connection = $entityManager->getConnection();
        $chamiloSchema = $connection->getSchemaManager();
        $pluginSchema = new \Doctrine\DBAL\Schema\Schema();
        $platform = $connection->getDatabasePlatform();

        if ($chamiloSchema->tablesExist([self::TABLE_TOOL])) {
            return false;
        }

        $toolTable = $pluginSchema->createTable(self::TABLE_TOOL);
        $toolTable->addColumn(
            'id',
            \Doctrine\DBAL\Types\Type::INTEGER,
            ['autoincrement' => true, 'unsigned' => true]
        );
        $toolTable->addColumn('name', \Doctrine\DBAL\Types\Type::STRING);
        $toolTable->addColumn('description', \Doctrine\DBAL\Types\Type::TEXT, ['notnull' => false]);
        $toolTable->addColumn('launch_url', \Doctrine\DBAL\Types\Type::TEXT);
        $toolTable->addColumn('consumer_key', \Doctrine\DBAL\Types\Type::STRING);
        $toolTable->addColumn('shared_secret', \Doctrine\DBAL\Types\Type::STRING);
        $toolTable->addColumn('custom_params', \Doctrine\DBAL\Types\Type::TEXT);
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
     */
    private function clearDatabase()
    {
        $entityManager = Database::getManager();
        $connection = $entityManager->getConnection();
        $chamiloSchema = $connection->getSchemaManager();

        if (!$chamiloSchema->tablesExist([self::TABLE_TOOL])) {
            return false;
        }

        $sql = 'DROP TABLE IF EXISTS ' . self::TABLE_TOOL;
        Database::query($sql);

        return true;
    }

    /**
     * Set the course settings
     */
    private function setCourseSettings()
    {
        $button = Display::toolbarButton(
            $this->get_lang('AddExternalTool'),
            api_get_path(WEB_PLUGIN_PATH) . 'ims_lti/add.php',
            'cog',
            'primary'
        );

        $this->course_settings = [
            [
                'name' => $this->get_lang('ImsLtiDescription') . $button . '<hr>',
                'type' => 'html'
            ]
        ];
    }

    /**
     * Add the course tool
     * @param \Chamilo\CoreBundle\Entity\Course $course
     * @param ImsLtiTool $tool
     */
    public function addCourseTool(\Chamilo\CoreBundle\Entity\Course $course, ImsLtiTool $tool)
    {
        $em = Database::getManager();

        $cToolId = AddCourse::generateToolId($course->getId());

        $cTool = new \Chamilo\CourseBundle\Entity\CTool();
        $cTool
            ->setId($cToolId)
            ->setCId($course->getId())
            ->setName($tool->getName())
            ->setLink($this->get_name() . '/start.php?' . http_build_query(['id' => $tool->getId()]))
            ->setImage($this->get_name() . '.png')
            ->setVisibility(1)
            ->setAdmin(0)
            ->setAddress('squaregray.gif')
            ->setAddedTool('NO')
            ->setTarget('_self')
            ->setCategory('plugin')
            ->setSessionId(0);

        $em->persist($cTool);
        $em->flush();
    }

    protected function getConfigExtraText()
    {
        $text = $this->get_lang('ImsLtiDescription');
        $text .= sprintf(
            $this->get_lang('ManageToolButton'),
            api_get_path(WEB_PLUGIN_PATH) . 'ims_lti/list.php'
        );

        return $text;
    }
}
