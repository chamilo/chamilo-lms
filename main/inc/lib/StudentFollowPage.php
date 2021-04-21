<?php

/* For licensing terms, see /license.txt */

/**
 * Class StudentFollowPage.
 */
class StudentFollowPage
{
    const VARIABLE_ACQUISITION = 'acquisition';

    public static function getLpAcquisition(
        array $lpInfo,
        int $studentId,
        int $courseId,
        int $sessionId = 0
    ): string {
        $tblLpView = Database::get_course_table(TABLE_LP_VIEW);

        $sessionCondition = api_get_session_condition($sessionId);

        $sql = "SELECT iid FROM $tblLpView
            WHERE c_id = $courseId AND lp_id = {$lpInfo['iid']} AND user_id = $studentId $sessionCondition
            ORDER BY view_count DESC";
        $lpView = Database::fetch_assoc(Database::query($sql));

        if (empty($lpView)) {
            return '-';
        }

        $extraField = new ExtraField('lp_view');
        $field = $extraField->get_handler_field_info_by_field_variable(self::VARIABLE_ACQUISITION);

        $extraFieldValue = new ExtraFieldValue('lp_view');
        $value = $extraFieldValue->get_values_by_handler_and_field_variable($lpView['iid'], self::VARIABLE_ACQUISITION);

        $return = '';

        if (empty($value)) {
            $return .= '-';
        } else {
            $optionSelected = array_filter(
                $field['options'],
                function (array $option) use ($value) {
                    return $option['option_value'] == $value['value'];
                }
            );

            if (empty($optionSelected)) {
                $return .= '-';
            } else {
                $optionSelected = current($optionSelected);
                $valueComment = json_decode($value['comment'], true);

                $register = api_get_user_entity($valueComment['user']);

                $return .= $optionSelected['display_text'].'<br>'
                    .Display::tag('small', $register->getCompleteName()).'<br>'
                    .Display::tag('small', api_convert_and_format_date($valueComment['datetime'], DATE_TIME_FORMAT_LONG));
            }
        }

        $return .= Display::toolbarButton(
            get_lang('Edit'),
            api_get_path(WEB_AJAX_PATH).'student_follow_page.ajax.php?'
            .http_build_query(['lp_view' => $lpView['iid'], 'a' => 'form_adquisition']),
            'refresh',
            'info',
            ['class' => 'btn-sm ajax', 'data-title' => $lpInfo['lp_name']]
        );

        return '<div id="acquisition-'.$lpView['iid'].'">'.$return.'</div>';
    }
}
