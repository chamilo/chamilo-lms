<?php
/* For licensing terms, see /license.txt */

/**
 * Library for generate a teacher time report
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.admin
 */
class TeacherTimeReport
{
    /**
     * The report data
     * @var array
     */
    public $data = array();

    /**
     * Callback for compare sessions names
     * @param array $dataA The data A
     * @param array $dataB The data B
     * @return int returns -1 if dataA is less than dataB, 1 if dataA is greater than dataB, and 0 if they are equal
     */
    public function compareSessions($dataA, $dataB)
    {
        return strnatcmp($dataA['session']['name'], $dataB['session']['name']);
    }

    /**
     * Callback for compare courses names
     * @param array $dataA The datab A
     * @param array $dataB The data B
     * @return int returns -1 if dataA is less than dataB, 1 if dataA is greater than dataB, and 0 if they are equal
     */
    public function compareCourses($dataA, $dataB)
    {
        return strnatcmp($dataA['course']['name'], $dataB['course']['name']);
    }

    /**
     * Callback for compare coaches names
     * @param array $dataA The datab A
     * @param array $dataB The data B
     * @return int returns -1 if dataA is less than dataB, 1 if dataA is greater than dataB, and 0 if they are equal
     */
    public function compareCoaches($dataA, $dataB)
    {
        return strnatcmp($dataA['coach']['completeName'], $dataB['coach']['completeName']);
    }

    /**
     * Sort the report data
     * @param boolean $withFilter Whether sort by sessions and courses
     */
    public function sortData($withFilter = false)
    {
        if ($withFilter) {
            uasort($this->data, array($this, 'compareSessions'));
            uasort($this->data, array($this, 'compareCourses'));
        }

        uasort($this->data, array($this, 'compareCoaches'));
    }

    /**
     * @param bool|false $withFilter
     * @return array
     */
    public function prepareDataToExport($withFilter = false)
    {
        $dataToExport = array();

        if ($withFilter) {
            $dataToExport[] = array(
                get_lang('Session'),
                get_lang('Course'),
                get_lang('Coach'),
                get_lang('TotalTime')
            );
        } else {
            $dataToExport[] = array(
                get_lang('Coach'),
                get_lang('TotalTime')
            );
        }

        foreach ($this->data as $row) {
            $data = array();

            if ($withFilter) {
                $data[] = $row['session']['name'];
                $data[] = $row['course']['name'];
            }

            $data[] = $row['coach']['completeName'];
            $data[] = $row['totalTime'];

            $dataToExport[] = $data;
        }

        return $dataToExport;
    }
}
