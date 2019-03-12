<?php
/* For licensing terms, see /license.txt */

/**
 * Class QuestionOptionsEvaluationPlugin.
 */
class QuestionOptionsEvaluationPlugin extends Plugin
{
    const EXTRAFIELD_FORMULA = 'question_valuation_formula';

    /**
     * QuestionValuationPlugin constructor.
     */
    protected function __construct()
    {
        $version = '1.0';
        $author = 'Angel Fernando Quiroz Campos';

        parent::__construct($version, $author, ['enable' => 'boolean']);
    }

    /**
     * @return QuestionOptionsEvaluationPlugin|null
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * @param int $exerciseId
     * @param int $iconSize
     *
     * @return string
     */
    public static function filterModify($exerciseId, $iconSize = ICON_SIZE_SMALL)
    {
        $directory = basename(__DIR__);
        $title = get_plugin_lang('plugin_title', self::class);
        $enabled = api_get_plugin_setting('questionoptionsevaluation', 'enable');

        if ('true' !== $enabled) {
            return '';
        }

        return Display::url(
            Display::return_icon('reload.png', $title, [], $iconSize),
            api_get_path(WEB_PATH)."plugin/$directory/evaluation.php?exercise=$exerciseId",
            [
                'class' => 'ajax',
                'data-size' => 'md',
                'data-title' => get_plugin_lang('plugin_title', self::class),
            ]
        );
    }

    public function install()
    {
        $this->createExtraField();
    }

    public function uninstall()
    {
        $this->removeExtraField();
    }

    /**
     * @return Plugin
     */
    public function performActionsAfterConfigure()
    {
        return $this;
    }

    private function createExtraField()
    {
        $qEf = new ExtraField('question');

        if (false === $qEf->get_handler_field_info_by_field_variable(self::EXTRAFIELD_FORMULA)) {
            $qEf
                ->save(
                    [
                        'variable' => self::EXTRAFIELD_FORMULA,
                        'field_type' => ExtraField::FIELD_TYPE_TEXT,
                        'display_text' => $this->get_lang('EvaluationFormula'),
                        'visible_to_self' => false,
                        'changeable' => false,
                    ]
                );
        }
    }

    private function removeExtraField()
    {
        $extraField = new ExtraField('question');
        $qEf = $extraField->get_handler_field_info_by_field_variable(self::EXTRAFIELD_FORMULA);

        if (false !== $qEf) {
            $extraField->delete($qEf['id']);
        }
    }
}
