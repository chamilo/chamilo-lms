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
        $form->addText('username', get_lang('LoginName'), $cId > 0);
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

        $qb = $this->em->createQueryBuilder();
        $qb
            ->select('te AS exe, q.title, te.startDate, u.firstname, u.lastname, u.username')
            ->from(TrackEExercises::class, 'te')
            ->innerJoin(CQuiz::class, 'q', Join::WITH, 'te.exeExoId = q.iid')
            ->innerJoin(User::class, 'u', Join::WITH, 'te.exeUserId = u.id');

        $params = [];

        if ($cId) {
            $qb->andWhere($qb->expr()->eq('te.cId', ':cId'));

            $params['cId'] = $cId;
        }

        $sessionItemIdList = $this->getSessionIdFromFormValues(
            $formValues,
            $this->plugin->getSessionFieldList()
        );

        if ($sessionItemIdList) {
            $qb->andWhere($qb->expr()->in('te.sessionId', ':sessionItemIdList'));

            $params['sessionItemIdList'] = $sessionItemIdList;
        } else {
            $qb->andWhere($qb->expr()->isNull('te.sessionId'));
        }

        if (!empty($formValues['username'])) {
            $qb->andWhere($qb->expr()->eq('u.username', ':username'));

            $params['username'] = $formValues['username'];
        }

        if (!empty($formValues['firstname'])) {
            $qb->andWhere($qb->expr()->eq('u.firstname', ':firstname'));

            $params['firstname'] = $formValues['firstname'];
        }

        if (!empty($formValues['lastname'])) {
            $qb->andWhere($qb->expr()->eq('u.lastname', ':lastname'));

            $params['lastname'] = $formValues['lastname'];
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
            $motive = get_lang('ExerciseFinished');

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

            $results[] = [
                'id' => $value['exe']->getExeId(),
                'quiz_title' => $value['title'],
                'username' => $value['username'],
                'user_fullname' => api_get_person_name($value['firstname'], $value['lastname']),
                'start_date' => $value['exe']->getStartDate(),
                'end_date' => $value['exe']->getExeDate(),
                'count_outfocused' => $outfocusedCount,
                'count_return' => $returnCount,
                'motive' => Display::span($motive, ['class' => "text-$class"]),
                'class' => $class,
            ];
        }

        return $results;
    }

    protected function createTable(array $tableData): HTML_Table
    {
        $pluginMonitoring = ExerciseMonitoringPlugin::create();
        $isPluginMonitoringEnabled = $pluginMonitoring->isEnabled(true);

        $detailIcon = Display::return_icon('forum_listview.png', get_lang('Detail'));

        $urlDetail = api_get_path(WEB_PLUGIN_PATH).'exercisefocused/pages/detail.php?'.api_get_cidreq().'&';

        $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
        $table->setHeaderContents(0, 0, get_lang('LoginName'));
        $table->setHeaderContents(0, 1, get_lang('FullUserName'));
        $table->setHeaderContents(0, 2, get_lang('Exercise'));
        $table->setHeaderContents(0, 3, get_lang('StartDate'));
        $table->setHeaderContents(0, 4, get_lang('EndDate'));
        $table->setHeaderContents(0, 5, $this->plugin->get_lang('Outfocused'));
        $table->setHeaderContents(0, 6, $this->plugin->get_lang('Returns'));
        $table->setHeaderContents(0, 7, $this->plugin->get_lang('Motive'));
        $table->setHeaderContents(0, 8, get_lang('Actions'));

        $row = 1;

        foreach ($tableData as $result) {
            $url = Display::url(
                $detailIcon,
                $urlDetail.http_build_query(['id' => $result['id']]),
                [
                    'class' => 'ajax',
                    'data-title' => get_lang('Detail'),
                ]
            );

            if ($isPluginMonitoringEnabled) {
                $url .= $pluginMonitoring->generateDetailLink((int) $result['id']);
            }

            $table->setCellContents($row, 0, $result['username']);
            $table->setCellContents($row, 1, $result['user_fullname']);
            $table->setCellContents($row, 2, $result['quiz_title']);
            $table->setCellContents($row, 3, api_get_local_time($result['start_date'], null, null, true, true, true));
            $table->setCellContents($row, 4, api_get_local_time($result['end_date'], null, null, true, true, true));
            $table->setCellContents($row, 5, $result['count_outfocused']);
            $table->setCellContents($row, 6, $result['count_return']);
            $table->setCellContents($row, 7, $result['motive']);
            $table->setCellContents($row, 8, $url);

            $table->setRowAttributes($row, ['class' => $result['class']], true);

            $row++;
        }

        $table->setColAttributes(3, ['class' => 'text-center']);
        $table->setColAttributes(4, ['class' => 'text-center']);
        $table->setColAttributes(5, ['class' => 'text-right']);
        $table->setColAttributes(6, ['class' => 'text-right']);
        $table->setColAttributes(7, ['class' => 'text-center']);
        $table->setColAttributes(8, ['class' => 'text-right']);

        return $table;
    }

    protected function findRandomResults(int $exerciseId): array
    {
        $percentage = (int) $this->plugin->get(\ExerciseFocusedPlugin::SETTING_PERCENTAGE_SAMPLING);

        if (empty($percentage)) {
            return [];
        }

        $cId = api_get_course_int_id();
        $sId = api_get_session_id();

        $tblTrackExe = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

        $sessionCondition = api_get_session_condition($sId);

        $result = Database::query(
            "SELECT exe_id FROM $tblTrackExe
            WHERE c_id = $cId
                AND exe_exo_id = $exerciseId
                $sessionCondition
            ORDER BY RAND() LIMIT $percentage"
        );

        $exeIdList = array_column(
            Database::store_result($result),
            'exe_id'
        );

        if (!$exeIdList) {
            return [];
        }

        $qb = $this->em->createQueryBuilder();
        $qb
            ->select('te AS exe, q.title, te.startDate, u.firstname, u.lastname, u.username')
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

    private function getSessionIdFromFormValues(array $formValues, array $fieldVariableList): array
    {
        $fieldItemIdList = [];
        $objFieldValue = new ExtraFieldValue('session');

        foreach ($fieldVariableList as $fieldVariable) {
            if (!isset($formValues["extra_$fieldVariable"])) {
                continue;
            }

            $itemValue = $objFieldValue->get_item_id_from_field_variable_and_field_value(
                $fieldVariable,
                $formValues["extra_$fieldVariable"]
            );

            if ($itemValue) {
                $fieldItemIdList[] = (int) $itemValue['item_id'];
            }
        }

        return array_unique($fieldItemIdList);
    }
}
