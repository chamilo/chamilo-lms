<?php

declare(strict_types=1);
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImport;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImportLibrary;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImportResults;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Mapping\ClassMetadata;
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
        $em = Database::getManager();

        $lpItemView = $em->createQueryBuilder()
            ->select('v.iid AS iid', 'v.score AS score')
            ->from(CLpItemView::class, 'v')
            ->where('v.iid = :iid')
            ->orderBy('v.viewCount', 'DESC')
            ->setMaxResults(1)
            ->setParameter('iid', $lpItemId)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (empty($lpItemView['iid'])) {
            return 0;
        }

        $duration = $em->getConnection()->createQueryBuilder()
            ->select('SUM(total_time) AS exe_duration')
            ->from(self::TBL_H5P_IMPORT_RESULTS)
            ->where('user_id = :userId')
            ->andWhere('c_lp_item_view_id = :viewId')
            ->setParameter('userId', $userId)
            ->setParameter('viewId', (int) $lpItemView['iid'])
            ->executeQuery()
            ->fetchOne()
        ;

        if (!empty($duration)) {
            $em->createQueryBuilder()
                ->update(CLpItemView::class, 'v')
                ->set('v.totalTime', ':totalTime')
                ->where('v.iid = :iid')
                ->setParameter('totalTime', (int) $duration)
                ->setParameter('iid', (int) $lpItemView['iid'])
                ->getQuery()
                ->execute()
            ;

            return (int) $duration;
        }

        $em->createQueryBuilder()
            ->update(CLpItemView::class, 'v')
            ->set('v.status', ':status')
            ->set('v.totalTime', '0')
            ->where('v.iid = :iid')
            ->setParameter('status', 'not attempted')
            ->setParameter('iid', (int) $lpItemView['iid'])
            ->getQuery()
            ->execute()
        ;

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
     * The browser computes the score, so the only thing to hold it to is its own maximum.
     */
    public static function isValidScore(int $score, int $maxScore): bool
    {
        return $score >= 0 && $score <= $maxScore;
    }

    /**
     * Whether a learning path item is the one that launches this content.
     */
    public static function isLpItemOfContent(CLpItem $lpItem, H5pImport $h5pImport): bool
    {
        return 'h5p' === $lpItem->getItemType() && (int) $lpItem->getPath() === $h5pImport->getIid();
    }

    /**
     * Create only the plugin schema.
     *
     * Course tool propagation is handled by the Chamilo 2 course-plugin flow.
     *
     * @throws ToolsException
     * @throws Exception
     */
    public function install(): void
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
        $this->executeSchemaSql($schemaTool->getCreateSchemaSql($this->getPluginMetadata()));
    }

    /**
     * @throws Exception
     */
    public function uninstall(): void
    {
        $em = Database::getManager();

        if ($em->getConnection()->createSchemaManager()->tablesExist([
            self::TBL_H5P_IMPORT,
            self::TBL_H5P_IMPORT_LIBRARY,
            self::TBL_H5P_IMPORT_RESULTS,
        ])) {
            $schemaTool = new SchemaTool($em);
            $this->executeSchemaSql($schemaTool->getDropSchemaSQL($this->getPluginMetadata()));
        }

        $this->uninstall_course_fields_in_all_courses();
        $this->removeH5pDirectories();
    }

    /**
     * @return array<int, ClassMetadata>
     */
    private function getPluginMetadata(): array
    {
        $em = Database::getManager();

        return [
            $em->getClassMetadata(H5pImport::class),
            $em->getClassMetadata(H5pImportLibrary::class),
            $em->getClassMetadata(H5pImportResults::class),
        ];
    }

    /**
     * SchemaTool generates the SQL of the plugin tables, but the PdoSessionHandlerSchemaListener registered in
     * config/services.yaml adds the php_session table to every schema it generates: creating it fails when it exists
     * (always), and dropping it would delete the sessions of every user. That table is not the plugin's: skip it.
     *
     * @param string[] $statements
     */
    private function executeSchemaSql(array $statements): void
    {
        $connection = Database::getManager()->getConnection();
        foreach ($statements as $statement) {
            if (str_contains($statement, 'php_session')) {
                continue;
            }
            $connection->executeStatement($statement);
        }
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
            ])
        ;

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

    // TODO(db-neutral): not migrated, no characterization test (uninstall-time filesystem cleanup)
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
