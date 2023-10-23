<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\ExerciseMonitoring\Entity\Log;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Symfony\Component\Filesystem\Filesystem;

class ExerciseMonitoringPlugin extends Plugin
{
    public const SETTING_TOOL_ENABLE = 'tool_enable';

    public const FIELD_SELECTED = 'exercisemonitoring_selected';

    private const TABLE_LOG = 'plugin_exercisemonitoring_log';

    protected function __construct()
    {
        $version = '0.0.1';

        $settings = [
            self::SETTING_TOOL_ENABLE => 'boolean',
        ];

        parent::__construct(
            $version,
            "Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>",
            $settings
        );
    }

    public static function create(): self
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    /**
     * @throws ToolsException
     */
    public function install()
    {
        $em = Database::getManager();

        if ($em->getConnection()->getSchemaManager()->tablesExist([self::TABLE_LOG])) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema(
            [
                $em->getClassMetadata(Log::class),
            ]
        );

        $pluginDirName = api_get_path(SYS_UPLOAD_PATH).'plugins/exercisemonitoring';

        $fs = new Filesystem();
        $fs->mkdir(
            $pluginDirName,
            api_get_permissions_for_new_directories()
        );

        $objField = new ExtraField('exercise');
        $objField->save([
            'variable' => self::FIELD_SELECTED,
            'field_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            'display_text' => $this->get_title(),
            'visible_to_self' => true,
            'changeable' => true,
            'filter' => false,
        ]);
    }

    public function uninstall()
    {
        $em = Database::getManager();

        if (!$em->getConnection()->getSchemaManager()->tablesExist([self::TABLE_LOG])) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema(
            [
                $em->getClassMetadata(Log::class),
            ]
        );

        $objField = new ExtraField('exercise');
        $extraFieldInfo = $objField->get_handler_field_info_by_field_variable(self::FIELD_SELECTED);

        if ($extraFieldInfo) {
            $objField->delete($extraFieldInfo['id']);
        }
    }

    public function getAdminUrl(): string
    {
        $name = $this->get_name();
        $webPath = api_get_path(WEB_PLUGIN_PATH).$name;

        return "$webPath/admin.php";
    }

    public function generateDetailLink(int $id): string
    {
        $title = $this->get_lang('ExerciseMonitored');
        $webcamIcon = Display::return_icon('webcam.png', $title);

        $monitoringDetailUrl = api_get_path(WEB_PLUGIN_PATH).'exercisemonitoring/pages/detail.php?'.api_get_cidreq()
            .'&'.http_build_query(['id' => $id]);

        return Display::url(
            $webcamIcon,
            $monitoringDetailUrl,
            [
                'class' => 'ajax',
                'data-title' => $title,
                'data-size' => 'lg',
            ]
        );
    }

    public static function generateSnapshotUrl(int $userId, string $imageFileName): string
    {
        $pluginDirName = api_get_path(WEB_UPLOAD_PATH).'plugins/exercisemonitoring';

        return $pluginDirName.'/'.$userId.'/'.$imageFileName;
    }
}
