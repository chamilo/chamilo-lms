<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\H5pImport\H5pImport;
use Chamilo\PluginBundle\Entity\H5pImport\H5pImportLibrary;
use Chamilo\PluginBundle\Entity\H5pImport\H5pImportResults;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Define the H5pImportPlugin class as an extension of Plugin
 * install/uninstall the plugin.
 */
class H5pImportPlugin extends Plugin
{
    public const TBL_H5P_IMPORT = 'plugin_h5p_import';
    public const TBL_H5P_IMPORT_LIBRARY = 'plugin_h5p_import_library';
    public const TBL_H5P_IMPORT_RESULTS = 'plugin_h5p_import_results';

    protected function __construct()
    {
        $settings = [
            'tool_enable' => 'boolean',
            'frame' => 'boolean',
            'embed' => 'boolean',
            'copyright' => 'boolean',
            'icon' => 'boolean',
        ];

        parent::__construct(
            '0.1',
            'Borja Sanchez',
            $settings
        );
    }

    public static function create(): ?H5pImportPlugin
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Updates and returns the total duration in the view of an H5P learning path item in a course.
     *
     * @param int $lpItemId The ID of the learning path item
     * @param int $userId   The user ID
     *
     * @return int The updated total duration in the learning path item view
     */
    public static function fixTotalTimeInLpItemView(
        int $lpItemId,
        int $userId
    ): int {
        $lpItemViewTable = Database::get_course_table(TABLE_LP_ITEM_VIEW);

        $sql = "SELECT iid, score
            FROM $lpItemViewTable
            WHERE
                iid = $lpItemId
            ORDER BY view_count DESC
            LIMIT 1";
        $responseItemView = Database::query($sql);
        $lpItemView = Database::fetch_array($responseItemView);

        // Get the total execution duration of the user in the learning path item view
        $sql = 'SELECT SUM(total_time) AS exe_duration
            FROM plugin_h5p_import_results
            WHERE
                user_id = '.$userId.' AND
                c_lp_item_view_id = '.$lpItemView['iid'].
            ' ORDER BY total_time DESC';
        $sumScoreResult = Database::query($sql);
        $durationRow = Database::fetch_array($sumScoreResult, 'ASSOC');

        if (!empty($durationRow['exe_duration'])) {
            // Update the total duration in the learning path item view
            $sqlUpdate = 'UPDATE '.$lpItemViewTable.'
                SET total_time = '.$durationRow['exe_duration'].'
                WHERE iid = '.$lpItemView['iid'];
            Database::query($sqlUpdate);

            return (int) $durationRow['exe_duration'];
        } else {
            // Update c_lp_item_view status
            $sqlUpdate = 'UPDATE '.$lpItemViewTable.'
                SET status = "not attempted",
                total_time = 0
                WHERE iid = '.$lpItemView['iid'];
            Database::query($sqlUpdate);

            return 0;
        }
    }

    public function getToolTitle(): string
    {
        $title = $this->get_lang('plugin_title');

        if (!empty($title)) {
            return $title;
        }

        return $this->get_title();
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
                    self::TBL_H5P_IMPORT_LIBRARY,
                    self::TBL_H5P_IMPORT_RESULTS,
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
                $em->getClassMetadata(H5pImportResults::class),
            ]
        );
        $this->addCourseTools();
    }

    public function addCourseTool(int $courseId)
    {
        // The $link param is set to "../plugin" as a hack to link correctly to the plugin URL in course tool.
        // Otherwise, the link en the course tool will link to "/main/" URL.
        $this->createLinkToCourseTool(
            $this->get_lang('plugin_title'),
            $courseId,
            'plugin_h5p_import.png',
            '../plugin/h5pimport/start.php',
            0,
            'authoring'
        );
    }

    public function uninstall()
    {
        $em = Database::getManager();

        if (!$em->getConnection()
            ->getSchemaManager()
            ->tablesExist(
                [
                    self::TBL_H5P_IMPORT,
                    self::TBL_H5P_IMPORT_LIBRARY,
                    self::TBL_H5P_IMPORT_RESULTS,
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
                $em->getClassMetadata(H5pImportResults::class),
            ]
        );
        $this->deleteCourseToolLinks();
        $this->removeH5pDirectories();
    }

    /**
     * Perform actions after configuring the H5P import plugin.
     *
     * @return H5pImportPlugin The H5P import plugin instance.
     */
    public function performActionsAfterConfigure(): H5pImportPlugin
    {
        $this->deleteCourseToolLinks();

        if ('true' === $this->get('tool_enable')) {
            $this->addCourseTools();
        }

        return $this;
    }

    /**
     * Get the view URL for an H5P import.
     *
     * @param H5pImport $h5pImport The H5P import object.
     *
     * @return string The view URL for the H5P import.
     */
    public function getViewUrl(H5pImport $h5pImport): string
    {
        return api_get_path(WEB_PLUGIN_PATH).'h5pimport/view.php?id='.$h5pImport->getIid().'&'.api_get_cidreq();
    }

    /**
     * Generates the LP resource block for H5P imports.
     *
     * @param int $lpId The LP ID.
     *
     * @return string The HTML for the LP resource block.
     */
    public function getLpResourceBlock(int $lpId): string
    {
        $cidReq = api_get_cidreq(true, true, 'lp');
        $webPath = api_get_path(WEB_PLUGIN_PATH).'h5pimport/';
        $course = api_get_course_entity();
        $session = api_get_session_entity();

        $tools = Database::getManager()
            ->getRepository(H5pImport::class)
            ->findBy(['course' => $course, 'session' => $session]);

        $importIcon = Display::return_icon('plugin_h5p_import_upload.png');
        $moveIcon = Display::url(
            Display::return_icon('move_everywhere.png', get_lang('Move'), [], ICON_SIZE_TINY),
            '#',
            ['class' => 'moved']
        );

        $return = '<ul class="lp_resource">';
        $return .= '<li class="lp_resource_element">';
        $return .= $importIcon;
        $return .= Display::url(
            get_lang('Import'),
            $webPath."start.php?action=add&$cidReq&".http_build_query(['lp_id' => $lpId])
        );
        $return .= '</li>';

        /** @var H5pImport $tool */
        foreach ($tools as $tool) {
            $toolAnchor = Display::url(
                Security::remove_XSS($tool->getName()),
                api_get_self()."?$cidReq&"
                .http_build_query(
                    ['action' => 'add_item', 'type' => TOOL_H5P, 'file' => $tool->getIid(), 'lp_id' => $lpId]
                ),
                ['class' => 'moved']
            );

            $return .= Display::tag(
                'li',
                $moveIcon.$importIcon.$toolAnchor,
                [
                    'class' => 'lp_resource_element',
                    'data_id' => $tool->getIid(),
                    'data_type' => TOOL_H5P,
                    'title' => $tool->getName(),
                ]
            );
        }

        $return .= '</ul>';

        return $return;
    }

    /**
     * Add course tools for all courses.
     */
    private function addCourseTools(): void
    {
        $courses = Database::getManager()
            ->createQuery('SELECT c.id FROM ChamiloCoreBundle:Course c')
            ->getResult();

        foreach ($courses as $course) {
            $this->addCourseTool($course['id']);
        }
    }

    private function deleteCourseToolLinks()
    {
        Database::getManager()
            ->createQuery('DELETE FROM ChamiloCourseBundle:CTool t WHERE t.category = :category AND t.link LIKE :link')
            ->execute(['category' => 'authoring', 'link' => '../plugin/h5pimport/start.php%']);
    }

    /**
     * Removes H5P directories for all courses.
     */
    private function removeH5pDirectories(): void
    {
        $fs = new Filesystem();
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT id FROM $table ORDER BY id";
        $res = Database::query($sql);
        while ($row = Database::fetch_assoc($res)) {
            $courseInfo = api_get_course_info_by_id($row['id']);
            $fs->remove($courseInfo['course_sys_path'].'/h5p');
        }
    }
}
