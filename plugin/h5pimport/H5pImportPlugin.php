<?php
/* For licensing terms, see /license.txt */

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Chamilo\PluginBundle\Entity\H5pImport\H5pImport;
use Chamilo\PluginBundle\Entity\H5pImport\H5pImportLibrary;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Define the H5pImportPlugin class as an extension of Plugin
 * install/uninstall the plugin.
 */

class H5pImportPlugin extends Plugin
{

    public const TBL_H5P_IMPORT = 'plugin_h5p_import';
    public const TBL_H5P_IMPORT_LIBRARY = 'plugin_h5p_import_library';
    protected function __construct()
    {
        $settings = [
            'tool_enable' => 'boolean',
        ];

        parent::__construct(
            '0.1',
            'Borja Sanchez',
            $settings
        );
    }

    public function getToolTitle(): string
    {
        $title = $this->get_lang('plugin_title');

        if (!empty($title)) {
            return $title;
        }

        return $this->get_title();
    }

    public static function create(): ?H5pImportPlugin
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * @throws ToolsException
     */
    public function install()
    {
        $em = Database::getManager();
        if ($em->getConnection()
            ->getSchemaManager()
            ->tablesExist(
                [
                    self::TBL_H5P_IMPORT,
                    self::TBL_H5P_IMPORT_LIBRARY
                ]
            )
        ) {
            return;
        }


        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema(
            [
                $em->getClassMetadata(H5pImport::class),
                $em->getClassMetadata(H5pImportLibrary::class),
            ]
        );
        $this->addCourseTools();
    }
    public function uninstall()
    {
        $settings = [
            'tool_enable',
        ];

        $em = Database::getManager();
        $tableSettings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        $urlId = api_get_current_access_url_id();

        foreach ($settings as $variable) {
            $sql = "DELETE FROM $tableSettings WHERE variable = '$variable' AND access_url = $urlId";
            Database::query($sql);
        }

        if (!$em->getConnection()
            ->getSchemaManager()
            ->tablesExist(
                [
                    self::TBL_H5P_IMPORT,
                    self::TBL_H5P_IMPORT_LIBRARY
                ]
            )
        ) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema(
            [
                $em->getClassMetadata(H5pImport::class),
                $em->getClassMetadata(H5pImportLibrary::class),
            ]
        );
        $this->removeH5pDirectories();
    }
    public function performActionsAfterConfigure(): H5pImportPlugin
    {
        $em = Database::getManager();

        $this->deleteCourseToolLinks();

        if ('true' === $this->get('tool_enable')) {
            $courses = $em->createQuery('SELECT c.id FROM ChamiloCoreBundle:Course c')->getResult();


            foreach ($courses as $course) {
                $this->createLinkToCourseTool($this->getToolTitle(), $course['id']);
            }
        }

        return $this;
    }
    public function getViewUrl(H5pImport $h5pImport): string
    {
        return api_get_path(WEB_PLUGIN_PATH).'h5pimport\view.php?id='.$h5pImport->getIid().'&'.api_get_cidreq();
    }

    private function addCourseTools()
    {
        $courses = Database::getManager()
            ->createQuery('SELECT c.id FROM ChamiloCoreBundle:Course c')
            ->getResult();

        foreach ($courses as $course) {
            $this->addCourseTool($course['id']);
        }
    }

    /**
     * @param int $courseId
     */
    public function addCourseTool(int $courseId)
    {
        // The $link param is set to "../plugin" as a hack to link correctly to the plugin URL in course tool.
        // Otherwise, the link en the course tool will link to "/main/" URL.
        $this->createLinkToCourseTool(
            $this->get_lang('plugin_title'),
            $courseId,
            'plugins.png',
            '../plugin/h5pimport/start.php',
            0,
            'authoring'
        );
    }
    private function deleteCourseToolLinks()
    {
        Database::getManager()
            ->createQuery('DELETE FROM ChamiloCourseBundle:CTool t WHERE t.category = :category AND t.link LIKE :link')
            ->execute(['category' => 'plugin', 'link' => 'h5pimport/start.php%']);
    }

    private function removeH5pDirectories()
    {

        $fs = new Filesystem();
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT id FROM $table
                ORDER BY id";
        $res = Database::query($sql);
        while ($row = Database::fetch_assoc($res)) {
            $courseInfo =  api_get_course_info_by_id($row['id']);
            $fs->remove($courseInfo['course_sys_path'].'/h5p');
        }
    }
}
