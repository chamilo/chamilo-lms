<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseFocused\Traits;

use Chamilo\PluginBundle\ExerciseFocused\Entity\Log;
use Display;
use Exception;
use ExtraField;
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

        $form = new FormValidator('exercisefocused', 'get');
        $form->addText('username', get_lang('Username'));
        $form->addText('firstname', get_lang('FirstName'));
        $form->addText('lastname', get_lang('LastName'));

        if ($extraFieldNameList) {
            $sId = api_get_session_id();

            (new ExtraField('session'))
                ->addElements(
                    $form,
                    $sId,
                    [],
                    false,
                    false,
                    $extraFieldNameList,
                    [],
                    [],
                    false,
                    false,
                    [],
                    [],
                    [],
                    [],
                    $extraFieldNameList
                );

            if ($sId) {
                $extraNames = [];

                foreach ($extraFieldNameList as $key => $value) {
                    $extraNames[$key] = "extra_$value";
                }

                $form->freeze($extraNames);
            }
        }

        $form->addDatePicker('start_date', get_lang('StartDate'));
        $form->addRule('start_date', get_lang('ThisFieldIsRequired'), 'required');
        $form->addButtonSearch(get_lang('Search'));
        //$form->protect();

        return $form;
    }

    /**
     * @throws Exception
     */
    protected function findResults(array $formValues = []): array
    {
        $fieldVariableList = $this->plugin->getSessionFieldList();

        $params = [];

        $dql = 'SELECT te AS exe, u.firstname, u.lastname, u.username
            FROM Chamilo\CoreBundle\Entity\TrackEExercises te
            INNER JOIN Chamilo\UserBundle\Entity\User u WITH te.exeUserId = u.id
            INNER JOIN Chamilo\CoreBundle\Entity\Session s WITH te.sessionId = s.id';

        foreach ($fieldVariableList as $key => $fieldVariable) {
            if (!isset($formValues["extra_$fieldVariable"])) {
                continue;
            }

            $dql .= "
                INNER JOIN Chamilo\CoreBundle\Entity\ExtraFieldValues fv$key
                    WITH s.id = fv$key.itemId
                INNER JOIN Chamilo\CoreBundle\Entity\ExtraField f$key
                    WITH fv$key.field = f$key AND f$key.variable = :variable$key";

            $params["variable$key"] = $fieldVariable;
        }

        $dql .= '
            WHERE 1 = 1';

        if (isset($formValues['username'])) {
            $params['username'] = $formValues['username'];
            $params['firstname'] = $formValues['firstname'];
            $params['lastname'] = $formValues['lastname'];

            $dql .= '
                AND (u.username = :username AND u.firstname = :firstname AND u.lastname = :lastname)';
        }

        if (isset($formValues['start_date'])) {
            $params['start_date'] = api_get_utc_datetime($formValues['start_date'].' 00:00:00', false, true);
            $params['end_date'] = api_get_utc_datetime($formValues['start_date'].' 23:59:59', false, true);

            $dql .= '
                AND (te.startDate >= :start_date AND te.startDate < :end_date)';
        }

        $dql .= '
            ORDER BY te.startDate';

        $result = $this->em
            ->createQuery($dql)
            ->setParameters($params)
            ->getResult();

        $results = [];

        foreach ($result as $value) {
            api_get_local_time();
            $results[] = [
                'id' => $value['exe']->getExeId(),
                'username' => $value['username'],
                'user_fullname' => api_get_person_name($value['firstname'], $value['lastname']),
                'start_date' => $value['exe']->getStartDate(),
                'end_date' => $value['exe']->getExeDate(),
                'count_outfocused' => $this->logRepository->countByActionInExe($value['exe'], Log::TYPE_OUTFOCUSED),
                'count_return' => $this->logRepository->countByActionInExe($value['exe'], Log::TYPE_RETURN),
                'abandonment' => $this->logRepository->countByActionInExe(
                    $value['exe'],
                    Log::TYPE_ABANDONMENT_LIMIT
                ) > 0,
                'time_limit' => $this->logRepository->countByActionInExe($value['exe'], Log::TYPE_TIME_LIMIT) > 0,
            ];
        }

        return $results;
    }

    protected function createTable(array $tableData): HTML_Table
    {
        $detailIcon = Display::return_icon('forum_listview.png', get_lang('Detail'));

        $urlDetail = api_get_path(WEB_PLUGIN_PATH).'exercisefocused/pages/detail.php?'.api_get_cidreq().'&';

        $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
        $table->setHeaderContents(0, 0, get_lang('Username'));
        $table->setHeaderContents(0, 1, get_lang('FullUserName'));
        $table->setHeaderContents(0, 2, get_lang('StartDate'));
        $table->setHeaderContents(0, 3, get_lang('EndDate'));
        $table->setHeaderContents(0, 4, $this->plugin->get_lang('Outfocused'));
        $table->setHeaderContents(0, 5, $this->plugin->get_lang('Returns'));
        $table->setHeaderContents(0, 6, $this->plugin->get_lang('MaxOutfocused'));
        $table->setHeaderContents(0, 7, $this->plugin->get_lang('TimeLimitReached'));
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

            $table->setCellContents($row, 0, $result['username']);
            $table->setCellContents($row, 1, $result['user_fullname']);
            $table->setCellContents($row, 2, api_get_local_time($result['start_date'], null, null, true, true, true));
            $table->setCellContents($row, 3, api_get_local_time($result['end_date'], null, null, true, true, true));
            $table->setCellContents($row, 4, $result['count_outfocused']);
            $table->setCellContents($row, 5, $result['count_return']);
            $table->setCellContents($row, 6, $result['abandonment'] ? get_lang('Yes') : '');
            $table->setCellContents($row, 7, $result['time_limit'] ? get_lang('Yes') : '');
            $table->setCellContents($row, 8, $url);

            $row++;
        }

        $table->setColAttributes(2, ['class' => 'text-center']);
        $table->setColAttributes(3, ['class' => 'text-center']);
        $table->setColAttributes(4, ['class' => 'text-right']);
        $table->setColAttributes(5, ['class' => 'text-right']);
        $table->setColAttributes(6, ['class' => 'text-center']);
        $table->setColAttributes(7, ['class' => 'text-center']);
        $table->setColAttributes(8, ['class' => 'text-right']);

        return $table;
    }
}
