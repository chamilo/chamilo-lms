<?php

/* For licensing terms, see /license.txt */

class MyStudents
{
    public static function userCareersTable(int $studentId): string
    {
        if (!api_get_configuration_value('allow_career_users')) {
            return '';
        }

        $careers = UserManager::getUserCareers($studentId);

        if (empty($careers)) {
            return '';
        }

        $title = Display::page_subheader(get_lang('Careers'), null, 'h3', ['class' => 'section-title']);

        return $title.self::getCareersTable($careers, $studentId);
    }

    public static function getCareersTable(array $careers, int $studentId): string
    {
        if (empty($careers)) {
            return '';
        }

        $webCodePath = api_get_path(WEB_CODE_PATH);
        $iconDiagram = Display::return_icon('multiplicate_survey.png', get_lang('Diagram'));
        $careerModel = new Career();

        $headers = [
            get_lang('Career'),
            get_lang('Diagram'),
        ];

        $data = array_map(
            function (array $careerInfo) use ($careerModel, $webCodePath, $iconDiagram, $studentId) {
                $careerId = $careerInfo['id'];
                if (api_get_configuration_value('use_career_external_id_as_identifier_in_diagrams')) {
                    $careerId = $careerModel->getCareerIdFromInternalToExternal($careerId);
                }

                $url = $webCodePath.'user/career_diagram.php?career_id='.$careerId.'&user_id='.$studentId;

                return [
                    $careerInfo['name'],
                    Display::url($iconDiagram, $url),
                ];
            },
            $careers
        );

        $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
        $table->setHeaders($headers);
        $table->setData($data);

        return $table->toHtml();
    }

    public static function getBlockForSkills(int $studentId, int $courseId, int $sessionId): string
    {
        $allowAll = api_get_configuration_value('allow_teacher_access_student_skills');

        if ($allowAll) {
            return Tracking::displayUserSkills($studentId, 0, 0, true);
        }

        // Default behaviour - Show all skills depending the course and session id
        return Tracking::displayUserSkills($studentId, $courseId, $sessionId);
    }

    public static function getBlockForClasses($studentId): ?string
    {
        $userGroupManager = new UserGroup();
        $userGroups = $userGroupManager->getNameListByUser(
            $studentId,
            UserGroup::NORMAL_CLASS
        );

        if (empty($userGroups)) {
            return null;
        }

        $headers = [get_lang('Classes')];
        $data = array_map(
            function ($class) {
                return [$class];
            },
            $userGroups
        );

        $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
        $table->setHeaders($headers);
        $table->setData($data);

        return $table->toHtml();
    }
}
