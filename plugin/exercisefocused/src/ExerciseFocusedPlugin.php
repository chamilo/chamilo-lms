<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\PluginBundle\ExerciseFocused\Entity\Log;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;

class ExerciseFocusedPlugin extends Plugin
{
    public const SETTING_TOOL_ENABLE = 'tool_enable';
    public const SETTING_ENABLE_TIME_LIMIT = 'enable_time_limit';
    public const SETTING_TIME_LIMIT = 'time_limit';
    public const SETTING_ENABLE_OUTFOCUSED_LIMIT = 'enable_outfocused_limit';
    public const SETTING_OUTFOCUSED_LIMIT = 'outfocused_limit';
    public const SETTING_SESSION_FIELD_FILTERS = 'session_field_filters';
    public const SETTING_PERCENTAGE_SAMPLING = 'percentage_sampling';

    public const FIELD_SELECTED = 'exercisefocused_selected';

    private const TABLE_LOG = 'plugin_exercisefocused_log';

    protected function __construct()
    {
        $settings = [
            self::SETTING_TOOL_ENABLE => 'boolean',
            self::SETTING_ENABLE_TIME_LIMIT => 'boolean',
            self::SETTING_TIME_LIMIT => 'text',
            self::SETTING_ENABLE_OUTFOCUSED_LIMIT => 'boolean',
            self::SETTING_OUTFOCUSED_LIMIT => 'text',
            self::SETTING_SESSION_FIELD_FILTERS => 'text',
            self::SETTING_PERCENTAGE_SAMPLING => 'text',
        ];

        parent::__construct(
            "0.0.1",
            "Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>",
            $settings
        );
    }

    public static function create(): ?ExerciseFocusedPlugin
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

    public function getActionTitle($action): string
    {
        switch ($action) {
            case Log::TYPE_OUTFOCUSED:
                return $this->get_lang('Outfocused');
            case Log::TYPE_RETURN:
                return $this->get_lang('Return');
            case Log::TYPE_OUTFOCUSED_LIMIT:
                return $this->get_lang('MaxOutfocusedReached');
            case Log::TYPE_TIME_LIMIT:
                return $this->get_lang('TimeLimitReached');
        }

        return '';
    }

    public function getLinkReporting(int $exerciseId): string
    {
        if (!$this->isEnabled(true)) {
            return '';
        }

        $values = (new ExtraFieldValue('exercise'))
            ->get_values_by_handler_and_field_variable($exerciseId, self::FIELD_SELECTED);

        if (!$values || !$values['value']) {
            return '';
        }

        $icon = Display::return_icon(
            'window_list_slide.png',
            $this->get_lang('ReportByAttempts'),
            [],
            ICON_SIZE_MEDIUM
        );

        $url = api_get_path(WEB_PLUGIN_PATH)
            .'exercisefocused/pages/reporting.php?'
            .api_get_cidreq().'&'.http_build_query(['id' => $exerciseId]);

        return Display::url($icon, $url);
    }

    public function getSessionFieldList(): array
    {
        $settingField = $this->get(self::SETTING_SESSION_FIELD_FILTERS);

        $fields = explode(',', $settingField);

        return array_map('trim', $fields);
    }

    public function isEnableForExercise(int $exerciseId): bool
    {
        $renderRegion = $this->isEnabled(true)
            && strpos($_SERVER['SCRIPT_NAME'], '/main/exercise/exercise_submit.php') !== false;

        if (!$renderRegion) {
            return false;
        }

        $objFieldValue = new ExtraFieldValue('exercise');
        $values = $objFieldValue->get_values_by_handler_and_field_variable(
            $exerciseId,
            self::FIELD_SELECTED
        );

        return $values && (bool) $values['value'];
    }

    public function calculateMotive(int $outfocusedLimitCount, int $timeLimitCount)
    {
        $motive = $this->get_lang('MotiveExerciseFinished');

        if ($outfocusedLimitCount > 0) {
            $motive = $this->get_lang('MaxOutfocusedReached');
        }

        if ($timeLimitCount > 0) {
            $motive = $this->get_lang('TimeLimitReached');
        }

        return $motive;
    }

    protected function createLinkToCourseTool($name, $courseId, $iconName = null, $link = null, $sessionId = 0, $category = 'plugin'): ?CTool
    {
        $tool = parent::createLinkToCourseTool($name, $courseId, $iconName, $link, $sessionId, $category);

        if (!$tool) {
            return null;
        }

        $tool->setName(
            $tool->getName().':teacher'
        );

        $em = Database::getManager();
        $em->persist($tool);
        $em->flush();

        return $tool;
    }
}
