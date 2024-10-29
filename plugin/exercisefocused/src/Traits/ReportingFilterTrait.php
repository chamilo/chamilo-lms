<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseFocused\Traits;

use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\PluginBundle\ExerciseFocused\Entity\Log;
use Chamilo\UserBundle\Entity\User;
use Database;
use Display;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use ExerciseFocusedPlugin;
use ExerciseMonitoringPlugin;
use ExtraField;
use ExtraFieldValue;
use FormValidator;
use HTML_Table;

trait ReportingFilterTrait
{
    /**
     * @throws Exception
     */
    protected function createForm(): FormValidator
    {
        $extraFieldNameList = $this->plugin->getSessionFieldList();
        $cId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        $form = new FormValidator('exercisefocused', 'get');
        $form->addText('username', get_lang('LoginName'), false);
        $form->addText('firstname', get_lang('FirstName'), false);
        $form->addText('lastname', get_lang('LastName'), false);

        if ($extraFieldNameList && ($sessionId || !$cId)) {
            (new ExtraField('session'))
                ->addElements(
                    $form,
                    $sessionId,
                    [],
                    false,
                    false,
                    $extraFieldNameList
                );

            $extraNames = [];

            foreach ($extraFieldNameList as $key => $value) {
                $extraNames[$key] = "extra_$value";
            }

            if ($sessionId) {
                $form->freeze($extraNames);
            }
        }

        $form->addDatePicker('start_date', get_lang('StartDate'));
        $form->addButtonSearch(get_lang('Search'));
        //$form->protect();

        return $form;
    }

    /**
     * @throws Exception
     */
    protected function findResults(array $formValues = []): array
    {
        $cId = api_get_course_int_id();
        $sId = api_get_session_id();

        $qb = $this->em->createQueryBuilder();
        $qb
            ->select('te AS exe, q.title, te.startDate, u.id AS user_id, u.firstname, u.lastname, u.username, te.sessionId, te.cId')
            ->from(TrackEExercises::class, 'te')
            ->innerJoin(CQuiz::class, 'q', Join::WITH, 'te.exeExoId = q.iid')
            ->innerJoin(User::class, 'u', Join::WITH, 'te.exeUserId = u.id');

        $params = [];

        if ($cId) {
            $qb->andWhere($qb->expr()->eq('te.cId', ':cId'));

            $params['cId'] = $cId;

            $sessionItemIdList = $sId ? [$sId] : [];
        } else {
            $sessionItemIdList = $this->getSessionIdFromFormValues(
                $formValues,
                $this->plugin->getSessionFieldList()
            );
        }

        if ($sessionItemIdList) {
            $qb->andWhere($qb->expr()->in('te.sessionId', ':sessionItemIdList'));

            $params['sessionItemIdList'] = $sessionItemIdList;
        }

        if (!empty($formValues['username'])) {
            $qb->andWhere($qb->expr()->eq('u.username', ':username'));

            $params['username'] = $formValues['username'];
        }

        if (!empty($formValues['firstname'])) {
            $qb->andWhere($qb->expr()->like('u.firstname', ':firstname'));

            $params['firstname'] = $formValues['firstname'].'%';
        }

        if (!empty($formValues['lastname'])) {
            $qb->andWhere($qb->expr()->like('u.lastname', ':lastname'));

            $params['lastname'] = $formValues['lastname'].'%';
        }

        if (!empty($formValues['start_date'])) {
            $qb->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->gte('te.startDate', ':start_date'),
                    $qb->expr()->lte('te.exeDate', ':end_date')
                )
            );

            $params['start_date'] = api_get_utc_datetime($formValues['start_date'].' 00:00:00', false, true);
            $params['end_date'] = api_get_utc_datetime($formValues['start_date'].' 23:59:59', false, true);
        }

        if (empty($params)) {
            return [];
        }

        if ($cId && !empty($formValues['id'])) {
            $qb->andWhere($qb->expr()->eq('q.iid', ':q_id'));

            $params['q_id'] = $formValues['id'];
        }

        $qb->setParameters($params);

        return $this->formatResults(
            $qb->getQuery()->getResult()
        );
    }

    protected function formatResults(array $queryResults): array
    {
        $results = [];

        foreach ($queryResults as $value) {
            $outfocusedCount = $this->logRepository->countByActionInExe($value['exe'], Log::TYPE_OUTFOCUSED);
            $returnCount = $this->logRepository->countByActionInExe($value['exe'], Log::TYPE_RETURN);
            $outfocusedLimitCount = $this->logRepository->countByActionInExe($value['exe'], Log::TYPE_OUTFOCUSED_LIMIT);
            $timeLimitCount = $this->logRepository->countByActionInExe($value['exe'], Log::TYPE_TIME_LIMIT);

            $class = 'success';
            $motive = $this->plugin->get_lang('MotiveExerciseFinished');

            if ($outfocusedCount > 0 || $returnCount > 0) {
                $class = 'warning';
            }

            if ($outfocusedLimitCount > 0 || $timeLimitCount > 0) {
                $class = 'danger';

                if ($outfocusedLimitCount > 0) {
                    $motive = $this->plugin->get_lang('MaxOutfocusedReached');
                }

                if ($timeLimitCount > 0) {
                    $motive = $this->plugin->get_lang('TimeLimitReached');
                }
            }

            $session = api_get_session_entity($value['sessionId']);
            $course = api_get_course_entity($value['cId']);

            $results[] = [
                'id' => $value['exe']->getExeId(),
                'quiz_title' => $value['title'],
                'user_id' => $value['user_id'],
                'username' => $value['username'],
                'firstname' => $value['firstname'],
                'lastname' => $value['lastname'],
                'start_date' => $value['exe']->getStartDate(),
                'end_date' => $value['exe']->getExeDate(),
                'count_outfocused' => $outfocusedCount,
                'count_return' => $returnCount,
                'motive' => Display::span($motive, ['class' => "text-$class"]),
                'class' => $class,
                'session_name' => $session ? $session->getName() : null,
                'course_title' => $course->getTitle(),
            ];
        }

        return $results;
    }

    protected function createTable(array $resultData): HTML_Table
    {
        $courseId = api_get_course_int_id();

        $pluginMonitoring = ExerciseMonitoringPlugin::create();
        $isPluginMonitoringEnabled = $pluginMonitoring->isEnabled(true);

        $detailIcon = Display::return_icon('forum_listview.png', get_lang('Detail'));

        $urlDetail = api_get_path(WEB_PLUGIN_PATH).'exercisefocused/pages/detail.php?'.api_get_cidreq().'&';

        $tableHeaders = [];
        $tableHeaders[] = get_lang('LoginName');
        $tableHeaders[] = get_lang('FirstName');
        $tableHeaders[] = get_lang('LastName');

        if (!$courseId) {
            $tableHeaders[] = get_lang('SessionName');
            $tableHeaders[] = get_lang('CourseTitle');
            $tableHeaders[] = get_lang('ExerciseName');
        }

        $tableHeaders[] = $this->plugin->get_lang('ExerciseStartDateAndTime');
        $tableHeaders[] = $this->plugin->get_lang('ExerciseEndDateAndTime');
        $tableHeaders[] = $this->plugin->get_lang('Outfocused');
        $tableHeaders[] = $this->plugin->get_lang('Returns');
        $tableHeaders[] = $this->plugin->get_lang('Motive');
        $tableHeaders[] = get_lang('Actions');

        $tableData = [];

        foreach ($resultData as $result) {
            $actionLinks = Display::url(
                $detailIcon,
                $urlDetail.http_build_query(['id' => $result['id']]),
                [
                    'class' => 'ajax',
                    'data-title' => get_lang('Detail'),
                ]
            );

            if ($isPluginMonitoringEnabled) {
                $actionLinks .= $pluginMonitoring->generateDetailLink(
                    (int) $result['id'],
                    $result['user_id']
                );
            }

            $row = [];

            $row[] = $result['username'];
            $row[] = $result['firstname'];
            $row[] = $result['lastname'];

            if (!$courseId) {
                $row[] = $result['session_name'];
                $row[] = $result['course_title'];
                $row[] = $result['quiz_title'];
            }

            $row[] = api_get_local_time($result['start_date'], null, null, true, true, true);
            $row[] = api_get_local_time($result['end_date'], null, null, true, true, true);
            $row[] = $result['count_outfocused'];
            $row[] = $result['count_return'];
            $row[] = $result['motive'];
            $row[] = $actionLinks;

            $tableData[] = $row;
        }

        $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
        $table->setHeaders($tableHeaders);
        $table->setData($tableData);
        $table->setColAttributes($courseId ? 3 : 6, ['class' => 'text-center']);
        $table->setColAttributes($courseId ? 4 : 7, ['class' => 'text-center']);
        $table->setColAttributes($courseId ? 5 : 8, ['class' => 'text-right']);
        $table->setColAttributes($courseId ? 6 : 9, ['class' => 'text-right']);
        $table->setColAttributes($courseId ? 7 : 10, ['class' => 'text-center']);
        $table->setColAttributes($courseId ? 8 : 11, ['class' => 'text-right']);

        foreach ($resultData as $idx => $result) {
            $table->setRowAttributes($idx + 1, ['class' => $result['class']], true);
        }

        return $table;
    }

    protected function findResultsInCourse(int $exerciseId, bool $randomResults = false): array
    {
        $exeIdList = $this->getAttemptsIdForExercise($exerciseId);

        if ($randomResults) {
            $exeIdList = $this->pickRandomAttempts($exeIdList) ?: $exeIdList;
        }

        if (empty($exeIdList)) {
            return [];
        }

        $qb = $this->em->createQueryBuilder();
        $qb
            ->select('te AS exe, q.title, te.startDate, u.id AS user_id, u.firstname, u.lastname, u.username, te.sessionId, te.cId')
            ->from(TrackEExercises::class, 'te')
            ->innerJoin(CQuiz::class, 'q', Join::WITH, 'te.exeExoId = q.iid')
            ->innerJoin(User::class, 'u', Join::WITH, 'te.exeUserId = u.id')
            ->andWhere(
                $qb->expr()->in('te.exeId', $exeIdList)
            )
            ->addOrderBy('te.startDate');

        return $this->formatResults(
            $qb->getQuery()->getResult()
        );
    }

    protected function findRandomResults(int $exerciseId): array
    {
        return $this->findResultsInCourse($exerciseId, true);
    }

    private function getSessionIdFromFormValues(array $formValues, array $fieldVariableList): array
    {
        $fieldItemIdList = [];
        $objFieldValue = new ExtraFieldValue('session');

        foreach ($fieldVariableList as $fieldVariable) {
            if (!isset($formValues["extra_$fieldVariable"])) {
                continue;
            }

            $itemValues = $objFieldValue->get_item_id_from_field_variable_and_field_value(
                $fieldVariable,
                $formValues["extra_$fieldVariable"],
                false,
                false,
                true
            );

            foreach ($itemValues as $itemValue) {
                $fieldItemIdList[] = (int) $itemValue['item_id'];
            }
        }

        return array_unique($fieldItemIdList);
    }

    private function getAttemptsIdForExercise(int $exerciseId): array
    {
        $cId = api_get_course_int_id();
        $sId = api_get_session_id();

        $tblTrackExe = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

        $sessionCondition = api_get_session_condition($sId);

        $result = Database::query(
            "SELECT exe_id FROM $tblTrackExe
            WHERE c_id = $cId
                AND exe_exo_id = $exerciseId
                $sessionCondition
            ORDER BY exe_id"
        );

        return array_column(
            Database::store_result($result),
            'exe_id'
        );
    }

    private function pickRandomAttempts(array $attemptIdList): array
    {
        $settingPercentage = (int) $this->plugin->get(ExerciseFocusedPlugin::SETTING_PERCENTAGE_SAMPLING);

        if (!$settingPercentage) {
            return [];
        }

        $percentage = count($attemptIdList) * ($settingPercentage / 100);
        $round = round($percentage) ?: 1;

        $random = (array) array_rand($attemptIdList, $round);

        $selection = [];

        foreach ($random as $rand) {
            $selection[] = $attemptIdList[$rand];
        }

        return $selection;
    }
}
