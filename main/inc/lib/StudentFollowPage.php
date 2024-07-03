<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CItemProperty;

/**
 * Class StudentFollowPage.
 */
class StudentFollowPage
{
    public const VARIABLE_ACQUISITION = 'acquisition';
    public const VARIABLE_INVISIBLE = 'invisible';

    public static function getLpSubscription(
        array $lpInfo,
        int $studentId,
        int $courseId,
        int $sessionId = 0,
        bool $showTeacherName = true
    ): string {
        $em = Database::getManager();

        if ($lpInfo['subscribe_users']) {
            $itemRepo = $em->getRepository(CItemProperty::class);
            $itemProperty = $itemRepo->findByUserSuscribedToItem(
                'learnpath',
                $lpInfo['iid'],
                $studentId,
                $courseId,
                $sessionId
            );

            if (null === $itemProperty) {
                $userGroups = GroupManager::getAllGroupPerUserSubscription($studentId, $courseId);

                foreach ($userGroups as $groupInfo) {
                    $itemProperty = $itemRepo->findByGroupSuscribedToLp(
                        'learnpath',
                        $lpInfo['iid'],
                        $groupInfo['iid'],
                        $courseId,
                        $sessionId
                    );

                    if (null !== $itemProperty) {
                        break;
                    }
                }
            }

            if (null === $itemProperty) {
                return '-';
            }

            $formattedDate = api_convert_and_format_date($itemProperty->getInsertDate(), DATE_TIME_FORMAT_LONG);

            if ($showTeacherName) {
                $insertUser = $itemProperty->getInsertUser()->getId() !== $studentId
                    ? $itemProperty->getInsertUser()->getCompleteName()
                    : '-';

                return "$insertUser<br>".Display::tag('small', $formattedDate);
            }

            return $formattedDate;
        }

        $subscriptionEvent = Event::findUserSubscriptionToCourse($studentId, $courseId, $sessionId);

        if (empty($subscriptionEvent)) {
            return '-';
        }

        $formattedDate = api_convert_and_format_date($subscriptionEvent['default_date'], DATE_TIME_FORMAT_LONG);

        if ($showTeacherName) {
            $creator = api_get_user_entity($subscriptionEvent['default_user_id']);

            return "{$creator->getCompleteName()}<br>".Display::tag('small', $formattedDate);
        }

        return $formattedDate;
    }

    public static function getLpAcquisition(
        array $lpInfo,
        int $studentId,
        int $courseId,
        int $sessionId = 0,
        bool $allowEdit = false
    ): string {
        $lpView = learnpath::findLastView($lpInfo['iid'], $studentId, $courseId, $sessionId, true);

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

                $return .= ExtraFieldOption::translateDisplayName($optionSelected['display_text']).'<br>'
                    .Display::tag('small', $register->getCompleteName()).'<br>'
                    .Display::tag(
                        'small',
                        api_convert_and_format_date($valueComment['datetime'], DATE_TIME_FORMAT_LONG)
                    ).'<br>';
            }
        }

        $editUrl = api_get_path(WEB_AJAX_PATH).'student_follow_page.ajax.php?'
            .http_build_query(['lp_view' => $lpView['iid'], 'a' => 'form_adquisition']);

        if ($allowEdit) {
            $return .= Display::url(
                Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_TINY),
                $editUrl,
                ['class' => 'ajax', 'data-title' => strip_tags($lpInfo['lp_name'])]
            );
        }

        return '<div id="acquisition-'.$lpView['iid'].'">'.$return.'</div>';
    }

    public static function getLpVisibleScript()
    {
        $url = api_get_path(WEB_AJAX_PATH).'student_follow_page.ajax.php?'.http_build_query(['a' => 'views_invisible']);

        return "<script>$(function () {
            var chkbView = $('[name=\"chkb_view[]\"]');

            function doRequest(element, state) {
                element.prop('disabled', true);

                var views = $.makeArray(element).map(function (input) { return input.value; });

                return $.post('$url', { 'chkb_view[]': views, 'state': state }, function () { element.prop('disabled', false); });
            }

            $('[name=\"chkb_category[]\"]').on('change', function () {
                var checked = this.checked;
                var chkbs = $(this).parents('table').find('td :checkbox').each(function () { this.checked = checked; });

                doRequest(chkbs, checked);
            }).prop('checked', true);

            chkbView.on('change', function () {
                doRequest($(this), this.checked);

                $(this).parents('table').find('th :checkbox').prop(
                    'checked',
                    $.makeArray($(this).parents('table').find('td :checkbox'))
                        .map(function (input) { return input.checked; })
                        .reduce(function (acc, cur) { return acc && cur; })
                );
            }).each(function () {
                if (!this.checked) {
                    $(this).parents('table').find('th :checkbox').prop('checked', false);
                }
            });
        });</script>";
    }

    public static function getLpVisibleField(array $lpInfo, int $studentId, int $courseId, int $sessionId = 0)
    {
        $attrs = [];

        $isVisible = self::isViewVisible($lpInfo['iid'], $studentId, $courseId, $sessionId);

        if (!$isVisible) {
            $attrs['checked'] = 'checked';
        }

        return Display::input(
            'checkbox',
            'chkb_view[]',
            implode('_', [$lpInfo['iid'], $studentId, $courseId, $sessionId]),
            $attrs
        );
    }

    public static function isViewVisible(int $lpId, int $studentId, int $courseId, int $sessionId): bool
    {
        $lpView = learnpath::findLastView($lpId, $studentId, $courseId, $sessionId);

        if (empty($lpView)) {
            return true;
        }

        $extraFieldValue = new ExtraFieldValue('lp_view');
        $value = $extraFieldValue->get_values_by_handler_and_field_variable($lpView['iid'], self::VARIABLE_INVISIBLE);

        return empty($value) || empty($value['value']);
    }
}
