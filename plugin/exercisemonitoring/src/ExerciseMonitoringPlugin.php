<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\ExerciseMonitoring\Entity\Log;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Symfony\Component\Filesystem\Filesystem;

class ExerciseMonitoringPlugin extends Plugin
{
    public const SETTING_TOOL_ENABLE = 'tool_enable';
    public const SETTING_INSTRUCTIONS = 'intructions';
    public const SETTING_INSTRUCTION_AGE_DISTINCTION_ENABLE = 'age_distinction_enable';
    public const SETTING_INSTRUCTION_LEGAL_AGE = 'legal_age';
    public const SETTING_EXTRAFIELD_BIRTHDATE = 'extrafield_birtdate';
    public const SETTING_INSTRUCTIONS_ADULTS = 'instructions_adults';
    public const SETTING_INSTRUCTIONS_MINORS = 'instructions_minors';
    public const SETTING_SNAPSHOTS_LIFETIME = 'snapshots_lifetime';

    public const FIELD_SELECTED = 'exercisemonitoring_selected';

    private const TABLE_LOG = 'plugin_exercisemonitoring_log';

    protected function __construct()
    {
        $version = '0.0.1';

        $settings = [
            self::SETTING_TOOL_ENABLE => 'boolean',
            self::SETTING_INSTRUCTIONS => 'wysiwyg',
            self::SETTING_INSTRUCTION_AGE_DISTINCTION_ENABLE => 'boolean',
            self::SETTING_INSTRUCTION_LEGAL_AGE => 'text',
            self::SETTING_EXTRAFIELD_BIRTHDATE => 'text',
            self::SETTING_INSTRUCTIONS_ADULTS => 'wysiwyg',
            self::SETTING_INSTRUCTIONS_MINORS => 'wysiwyg',
            self::SETTING_SNAPSHOTS_LIFETIME => 'text',
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

    public function generateDetailLink(int $exeId, int $userId): string
    {
        $title = $this->get_lang('ExerciseMonitored');
        $webcamIcon = Display::return_icon('webcam.png', $title);
        $webcamNaIcon = Display::return_icon('webcam_na.png', $this->get_lang('ExerciseUnmonitored'));

        $monitoringDetailUrl = api_get_path(WEB_PLUGIN_PATH).'exercisemonitoring/pages/detail.php?'.api_get_cidreq()
            .'&'.http_build_query(['id' => $exeId]);

        $url = Display::url(
            $webcamIcon,
            $monitoringDetailUrl,
            [
                'class' => 'ajax',
                'data-title' => $title,
                'data-size' => 'lg',
            ]
        );

        $showLink = true;

        if ('true' === $this->get(self::SETTING_INSTRUCTION_AGE_DISTINCTION_ENABLE) && !$this->isAdult($userId)) {
            $showLink = false;
        }

        return $showLink ? $url : $webcamNaIcon;
    }

    public static function generateSnapshotUrl(
        int $userId,
        string $imageFileName,
        string $path = WEB_UPLOAD_PATH
    ): string {
        $pluginDirName = api_get_path($path).'plugins/exercisemonitoring';

        return $pluginDirName.'/'.$userId.'/'.$imageFileName;
    }

    /**
     * @throws Exception
     */
    public function isAdult(int $userId = 0): bool
    {
        $userId = $userId ?: api_get_user_id();
        $fieldVariable = $this->get(self::SETTING_EXTRAFIELD_BIRTHDATE);
        $legalAge = (int) $this->get(self::SETTING_INSTRUCTION_LEGAL_AGE);

        $value = UserManager::get_extra_user_data_by_field($userId, $fieldVariable);

        if (empty($value)) {
            return false;
        }

        if (empty($value[$fieldVariable])) {
            return false;
        }

        $birthdate = new DateTime($value[$fieldVariable]);
        $now = new DateTime();
        $diff = $birthdate->diff($now);

        return !$diff->invert && $diff->y >= $legalAge;
    }
}
