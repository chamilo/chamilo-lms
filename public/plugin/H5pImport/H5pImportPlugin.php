<?php
/* For licensing terms, see /license.txt */

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
    public $isCoursePlugin = true;

    public const TBL_H5P_IMPORT = 'plugin_h5p_import';
    public const TBL_H5P_IMPORT_LIBRARY = 'plugin_h5p_import_library';
    public const TBL_H5P_IMPORT_RESULTS = 'plugin_h5p_import_results';

    protected function __construct()
    {
        $settings = [
            'frame' => 'boolean',
            'embed' => 'boolean',
            'copyright' => 'boolean',
            'icon' => 'boolean',
        ];

        parent::__construct(
            '0.4',
            'Borja Sanchez',
            $settings
        );
    }

    public static function create(): self
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    public function get_name(): string
    {
        return 'H5pImport';
    }

    /**
     * Updates and returns the total duration in the view of an H5P learning path item in a course.
     */
    public static function fixTotalTimeInLpItemView(
        int $lpItemId,
        int $userId
    ): int {
        $lpItemViewTable = Database::get_course_table(TABLE_LP_ITEM_VIEW);

        $sql = "SELECT iid, score
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
            WHERE user_id = '.$userId.' AND c_lp_item_view_id = '.$lpItemView['iid'];
        $sumScoreResult = Database::query($sql);
        $durationRow = Database::fetch_assoc($sumScoreResult);

        if (!empty($durationRow['exe_duration'])) {
            $sqlUpdate = 'UPDATE '.$lpItemViewTable.'
                SET total_time = '.$durationRow['exe_duration'].'
                WHERE iid = '.$lpItemView['iid'];
            Database::query($sqlUpdate);

            return (int) $durationRow['exe_duration'];
        }

        $sqlUpdate = 'UPDATE '.$lpItemViewTable.'
            SET status = "not attempted", total_time = 0
            WHERE iid = '.$lpItemView['iid'];
        Database::query($sqlUpdate);

        return 0;
    }

    public function getToolTitle(): string
    {
        $title = $this->get_lang('plugin_title');

        if (!empty($title)) {
            return $title;
        }

        return $this->get_title();
    }

    public function isToolEnabled(): bool
    {
        return 'true' === (string) $this->get('tool_enable');
    }

    /**
     * Create only the plugin schema.
     *
     * Course tool propagation is handled by the Chamilo 2 course-plugin flow.
     *
     * @throws ToolsException
     * @throws \Doctrine\DBAL\Exception
     */
    public function install()
    {
        $em = Database::getManager();

        if ($em->getConnection()->createSchemaManager()->tablesExist([
            self::TBL_H5P_IMPORT,
            self::TBL_H5P_IMPORT_LIBRARY,
            self::TBL_H5P_IMPORT_RESULTS,
        ])) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema([
            $em->getClassMetadata(H5pImport::class),
            $em->getClassMetadata(H5pImportLibrary::class),
            $em->getClassMetadata(H5pImportResults::class),
        ]);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function uninstall()
    {
        $em = Database::getManager();

        if ($em->getConnection()->createSchemaManager()->tablesExist([
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

        $this->uninstall_course_fields_in_all_courses();
        $this->removeH5pDirectories();
    }

    /**
     * Keep course tool links synchronized with the current plugin state.
     */
    public function performActionsAfterConfigure(): self
    {
        $this->syncCourseToolLinks();

        return $this;
    }

    /**
     * Compatibility helper for manual propagation or event subscribers.
     */
    public function addCourseTool(int $courseId): void
    {
        $this->install_course_fields($courseId, true);
    }

    public function getViewUrl(H5pImport $h5pImport): string
    {
        return api_get_path(WEB_PLUGIN_PATH).$this->get_name().'/view.php?id='.$h5pImport->getIid().'&'.api_get_cidreq();
    }

    public function getLpResourceBlock(int $lpId): string
    {
        $cidReq = api_get_cidreq(true, true, 'lp');
        $webPath = api_get_path(WEB_PLUGIN_PATH).$this->get_name().'/';
        $course = api_get_course_entity();
        $session = api_get_session_entity();

        $tools = Database::getManager()
            ->getRepository(H5pImport::class)
            ->findBy([
                'course' => $course,
                'session' => $session,
            ]);

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
            $webPath.'start.php?action=add&'.$cidReq.'&'.http_build_query(['lp_id' => $lpId])
        );
        $return .= '</li>';

        /** @var H5pImport $tool */
        foreach ($tools as $tool) {
            $toolAnchor = Display::url(
                Security::remove_XSS($tool->getName()),
                api_get_self().'?'.$cidReq.'&'.http_build_query([
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

    private function syncCourseToolLinks(): void
    {
        $this->uninstall_course_fields_in_all_courses();

        if ($this->isToolEnabled()) {
            $this->install_course_fields_in_all_courses();
        }
    }

    private function removeH5pDirectories(): void
    {
        $fs = new Filesystem();
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT id FROM $table ORDER BY id";
        $res = Database::query($sql);

        while ($row = Database::fetch_assoc($res)) {
            $courseInfo = api_get_course_info_by_id($row['id']);
            if (!empty($courseInfo['course_sys_path'])) {
                $fs->remove($courseInfo['course_sys_path'].'/h5p');
            }
        }
    }
}
