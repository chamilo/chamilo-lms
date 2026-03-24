<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImport;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImportLibrary;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImportResults;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * H5P import plugin bootstrap.
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
            '0.3',
            'Borja Sanchez',
            $settings
        );
    }

    public static function create(): ?self
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    /**
     * Updates and returns the total duration in the view of an H5P learning path item in a course.
     */
    public static function fixTotalTimeInLpItemView(int $lpItemId, int $userId): int
    {
        $lpItemViewTable = Database::get_course_table(TABLE_LP_ITEM_VIEW);

        $sql = "SELECT iid
                FROM $lpItemViewTable
                WHERE iid = $lpItemId
                ORDER BY view_count DESC
                LIMIT 1";
        $responseItemView = Database::query($sql);
        $lpItemView = Database::fetch_array($responseItemView);

        if (empty($lpItemView['iid'])) {
            return 0;
        }

        $sql = 'SELECT SUM(total_time) AS exe_duration
                FROM '.self::TBL_H5P_IMPORT_RESULTS.'
                WHERE user_id = '.(int) $userId.' AND c_lp_item_view_id = '.(int) $lpItemView['iid'];
        $sumScoreResult = Database::query($sql);
        $durationRow = Database::fetch_assoc($sumScoreResult);

        if (!empty($durationRow['exe_duration'])) {
            $sqlUpdate = 'UPDATE '.$lpItemViewTable.'
                          SET total_time = '.(int) $durationRow['exe_duration'].'
                          WHERE iid = '.(int) $lpItemView['iid'];
            Database::query($sqlUpdate);

            return (int) $durationRow['exe_duration'];
        }

        $sqlUpdate = 'UPDATE '.$lpItemViewTable.'
                      SET status = "not attempted", total_time = 0
                      WHERE iid = '.(int) $lpItemView['iid'];
        Database::query($sqlUpdate);

        return 0;
    }

    public function getToolTitle(): string
    {
        $title = (string) $this->get_lang('plugin_title');

        return '' !== trim($title) ? $title : $this->get_title();
    }

    public function isToolEnabled(): bool
    {
        $value = $this->get('tool_enable');

        if (null === $value || '' === $value) {
            return true;
        }

        return in_array($value, [true, 1, '1', 'true', 'yes', 'on'], true);
    }

    /**
     * @throws ToolsException
     * @throws \Doctrine\DBAL\Exception
     */
    public function install()
    {
        $em = Database::getManager();
        $schemaManager = $em->getConnection()->createSchemaManager();

        if (!$schemaManager->tablesExist([
            self::TBL_H5P_IMPORT,
            self::TBL_H5P_IMPORT_LIBRARY,
            self::TBL_H5P_IMPORT_RESULTS,
        ])) {
            $schemaTool = new SchemaTool($em);
            $schemaTool->createSchema([
                $em->getClassMetadata(H5pImport::class),
                $em->getClassMetadata(H5pImportLibrary::class),
                $em->getClassMetadata(H5pImportResults::class),
            ]);
        }

        $this->deleteCourseToolLinks();

        if ($this->isToolEnabled()) {
            $this->addCourseTools();
        }
    }

    public function addCourseTool(int $courseId): void
    {
        $this->createLinkToCourseTool(
            $this->getToolTitle(),
            $courseId,
            'plugin_h5p_import.png',
            '../plugin/H5pImport/start.php',
            0,
            'authoring'
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function uninstall()
    {
        $em = Database::getManager();
        $schemaManager = $em->getConnection()->createSchemaManager();

        if ($schemaManager->tablesExist([
            self::TBL_H5P_IMPORT,
            self::TBL_H5P_IMPORT_LIBRARY,
            self::TBL_H5P_IMPORT_RESULTS,
        ])) {
            $schemaTool = new SchemaTool($em);
            $schemaTool->dropSchema([
                $em->getClassMetadata(H5pImport::class),
                $em->getClassMetadata(H5pImportLibrary::class),
                $em->getClassMetadata(H5pImportResults::class),
            ]);
        }

        $this->deleteCourseToolLinks();
        $this->removeH5pDirectories();
    }

    public function performActionsAfterConfigure(): self
    {
        $this->deleteCourseToolLinks();

        if ($this->isToolEnabled()) {
            $this->addCourseTools();
        }

        return $this;
    }

    public function getViewUrl(H5pImport $h5pImport): string
    {
        return api_get_path(WEB_PLUGIN_PATH).'H5pImport/view.php?id='.$h5pImport->getIid().'&'.api_get_cidreq();
    }

    public function getLpResourceBlock(int $lpId): string
    {
        $cidReq = api_get_cidreq(true, true, 'lp');
        $webPath = api_get_path(WEB_PLUGIN_PATH).'H5pImport/';
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
                api_get_self()."?$cidReq&".http_build_query([
                    'action' => 'add_item',
                    'type' => TOOL_H5P,
                    'file' => $tool->getIid(),
                    'lp_id' => $lpId,
                ]),
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

    private function addCourseTools(): void
    {
        $courses = Database::getManager()
            ->createQuery('SELECT c.id FROM '.Course::class.' c')
            ->getArrayResult();

        foreach ($courses as $course) {
            $this->addCourseTool((int) $course['id']);
        }
    }

    private function deleteCourseToolLinks(): void
    {
        $em = Database::getManager();

        $em->createQuery('DELETE FROM '.CTool::class.' t WHERE t.link = :link')
            ->execute(['link' => '../plugin/H5pImport/start.php']);
    }

    private function removeH5pDirectories(): void
    {
        $fs = new Filesystem();
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT id FROM $table ORDER BY id";
        $res = Database::query($sql);

        while ($row = Database::fetch_assoc($res)) {
            $courseInfo = api_get_course_info_by_id((int) $row['id']);
            if (!empty($courseInfo['course_sys_path'])) {
                $fs->remove($courseInfo['course_sys_path'].'/h5p');
            }
        }
    }
}
