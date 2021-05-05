<?php

/* For licensing terms, see /license.txt */

/**
 * Class MyStudents.
 */
class MyStudents
{
    public static function getBlockForCareers(int $studentId): ?string
    {
        if (!api_get_configuration_value('allow_career_users')) {
            return null;
        }

        $careers = UserManager::getUserCareers($studentId);

        if (empty($careers)) {
            return null;
        }

        $webCodePath = api_get_path(WEB_CODE_PATH);
        $langDiagram = get_lang('Diagram');

        $headers = [
            get_lang('Career'),
            get_lang('Diagram'),
        ];

        $data = array_map(
            function (array $careerData) use ($webCodePath, $langDiagram) {
                $url = $webCodePath.'user/career_diagram.php?career_id='.$careerData['id'];

                return [
                    $careerData['name'],
                    Display::url($langDiagram, $url),
                ];
            },
            $careers
        );

        $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
        $table->setHeaders($headers);
        $table->setData($data);

        return Display::page_subheader(get_lang('Careers'), null, 'h3', ['class' => 'section-title'])
            .$table->toHtml();
    }

    public static function getBlockForSkills(int $studentId, int $courseId, int $sessionId): string
    {
        $allowAll = api_get_configuration_value('allow_teacher_access_student_skills');

        if ($allowAll) {
            // Show all skills
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
