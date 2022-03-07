<?php

/* For licensing terms, see /license.txt */

class Positioning extends Plugin
{
    public $isCoursePlugin = true;
    public $table;

    /**
     * Class constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            '1.0',
            'Julio Montoya',
            [
                'tool_enable' => 'boolean',
                'block_course_if_initial_exercise_not_attempted' => 'boolean',
                'average_percentage_to_unlock_final_exercise' => 'text',
            ]
        );

        $this->table = Database::get_main_table('plugin_positioning_exercise');
    }

    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    public function install()
    {
        $table = $this->table;

        $sql = 'CREATE TABLE IF NOT EXISTS '.$table.' (
                id INT unsigned NOT NULL auto_increment PRIMARY KEY,
                exercise_id INT unsigned NOT NULL,
                c_id INT unsigned NOT NULL,
                session_id INT unsigned DEFAULT NULL,
                is_initial TINYINT(1) NOT NULL,
                is_final TINYINT(1) NOT NULL
                )';
        Database::query($sql);

        // Installing course settings
        $this->install_course_fields_in_all_courses(true, 'positioning.png');
    }

    public function uninstall()
    {
        $table = $this->table;
        Database::query("DROP TABLE IF EXISTS $table");
        $this->uninstall_course_fields_in_all_courses();
    }

    public function isInitialExercise($exerciseId, $courseId, $sessionId)
    {
        $data = $this->getPositionData($exerciseId, $courseId, $sessionId);
        if ($data && isset($data['is_initial']) && 1 === (int) $data['is_initial']) {
            return true;
        }

        return false;
    }

    public function getPositionData($exerciseId, $courseId, $sessionId)
    {
        $table = $this->table;
        $exerciseId = (int) $exerciseId;
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;

        $sql = "SELECT * FROM $table
                WHERE
                    exercise_id = $exerciseId AND
                    c_id = $courseId AND
                    session_id = $sessionId
                    ";
        $result = Database::query($sql);

        if (Database::num_rows($result) > 0) {
            return Database::fetch_array($result, 'ASSOC');
        } elseif (0 !== $sessionId) {
            // if no exercise was set in the session, also look for it in the base course
            $sql = "SELECT * FROM $table
                WHERE
                    exercise_id = $exerciseId AND
                    c_id = $courseId AND
                    session_id = 0
                    ";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                return Database::fetch_array($result, 'ASSOC');
            }
        }

        return false;
    }

    public function isFinalExercise($exerciseId, $courseId, $sessionId)
    {
        $data = $this->getPositionData($exerciseId, $courseId, $sessionId);
        if ($data && isset($data['is_final']) && 1 === (int) $data['is_final']) {
            return true;
        }
    }

    public function setInitialExercise($exerciseId, $courseId, $sessionId)
    {
        $this->setOption('is_initial', $exerciseId, $courseId, $sessionId);
    }

    public function setFinalExercise($exerciseId, $courseId, $sessionId)
    {
        $this->setOption('is_final', $exerciseId, $courseId, $sessionId);
    }

    public function blockFinalExercise($userId, $exerciseId, $courseId, $sessionId)
    {
        $initialData = $this->getInitialExercise($courseId, $sessionId);

        if (empty($initialData)) {
            return false;
        }

        if ($initialData && isset($initialData['exercise_id'])) {
            // If this is final exercise?
            $finalData = $this->getFinalExercise($courseId, $sessionId);
            if (!empty($finalData) && $finalData['exercise_id'] && $exerciseId == $finalData['exercise_id']) {
                $initialResults = Event::getExerciseResultsByUser(
                    $userId,
                    $initialData['exercise_id'],
                    $courseId,
                    $sessionId
                );

                if (empty($initialResults)) {
                    return true;
                }

                $averageToUnlock = (float) $this->get('average_percentage_to_unlock_final_exercise');
                if (empty($averageToUnlock)) {
                    return false;
                }

                // Check average
                $courseInfo = api_get_course_info_by_id($courseId);
                $userAverage = (float) Tracking::get_avg_student_progress(
                    $userId,
                    $courseInfo['code'],
                    [],
                    $sessionId
                );

                if ($userAverage >= $averageToUnlock) {
                    return false;
                }

                return true;
            } else {
                return false;
            }
        }

        return true;
    }

    public function getInitialExercise($courseId, $sessionId)
    {
        return $this->getCourseExercise($courseId, $sessionId, true, false);
    }

    public function getFinalExercise($courseId, $sessionId)
    {
        return $this->getCourseExercise($courseId, $sessionId, false, true);
    }

    private function setOption($field, $exerciseId, $courseId, $sessionId)
    {
        if (!in_array($field, ['is_initial', 'is_final'], true)) {
            return false;
        }

        $data = $this->getPositionData($exerciseId, $courseId, $sessionId);
        $disableField = $field === 'is_initial' ? 'is_final' : 'is_initial';
        if ($data && isset($data['id'])) {
            $id = $data['id'];
            $sql = "UPDATE $this->table SET
                    $field = 1,
                    $disableField = 0
                    WHERE id = $id";
            Database::query($sql);

            $sql = "DELETE FROM $this->table
                    WHERE $field = 1 AND c_id = $courseId AND session_id = $sessionId AND id <> $id";
            Database::query($sql);
        } else {
            $params = [
                'exercise_id' => $exerciseId,
                'c_id' => $courseId,
                'session_id' => $sessionId,
                $field => 1,
                $disableField => 0,
            ];
            $id = Database::insert($this->table, $params);

            $sql = "DELETE FROM $this->table
                    WHERE $field = 1 AND c_id = $courseId AND session_id = $sessionId AND id <> $id";
            Database::query($sql);
        }
    }

    private function getCourseExercise($courseId, $sessionId, $isInitial, $isFinal)
    {
        $table = $this->table;
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;

        $sql = "SELECT * FROM $table
                WHERE
                    c_id = $courseId AND
                    session_id = $sessionId
                    ";
        $sqlEnd = '';

        if ($isInitial) {
            $sqlEnd .= ' AND is_initial = 1 ';
        } else {
            $sqlEnd .= ' AND is_initial = 0 ';
        }

        if ($isFinal) {
            $sqlEnd .= ' AND is_final = 1 ';
        } else {
            $sqlEnd .= ' AND is_final = 0 ';
        }
        $result = Database::query($sql.$sqlEnd);

        if (Database::num_rows($result) > 0) {
            return Database::fetch_array($result, 'ASSOC');
        } elseif (0 !== $sessionId) {
            $sql = "SELECT * FROM $table
                WHERE
                    c_id = $courseId AND
                    session_id = 0
                    ";
            $result = Database::query($sql.$sqlEnd);
            if (Database::num_rows($result) > 0) {
                return Database::fetch_array($result, 'ASSOC');
            }
        }

        return false;
    }
}
