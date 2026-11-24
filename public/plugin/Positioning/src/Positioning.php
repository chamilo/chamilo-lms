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
        // TODO(db-neutral): DDL (CREATE TABLE IF NOT EXISTS), not expressible in DQL nor the DBAL query builder
        Database::query($sql);

        // Installing course settings
        $this->install_course_fields_in_all_courses(true);
    }

    public function uninstall()
    {
        $table = $this->table;
        // TODO(db-neutral): DDL (DROP TABLE IF EXISTS), not expressible in DQL nor the DBAL query builder
        Database::query("DROP TABLE IF EXISTS $table");
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
        return Database::getManager()->getConnection()->createQueryBuilder()
            ->select('*')
            ->from($this->table)
            ->where('exercise_id = :exerciseId')
            ->andWhere('c_id = :courseId')
            ->andWhere('session_id = :sessionId')
            ->setParameter('exerciseId', (int) $exerciseId)
            ->setParameter('courseId', (int) $courseId)
            ->setParameter('sessionId', (int) $sessionId)
            ->executeQuery()
            ->fetchAssociative()
        ;
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

    public function shouldBlockUser(int $userId, int $courseId, ?int $sessionId): bool
    {
        if ($this->get('block_course_if_initial_exercise_not_attempted') !== 'true') {
            return false;
        }

        $initialData = $this->getInitialExercise($courseId, $sessionId);

        if (empty($initialData['exercise_id'])) {
            return false;
        }

        $results = \Event::getExerciseResultsByUser(
            $userId,
            (int) $initialData['exercise_id'],
            $courseId,
            $sessionId
        );

        return empty($results);
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

        $connection = Database::getManager()->getConnection();
        $data = $this->getPositionData($exerciseId, $courseId, $sessionId);
        $disableField = 'is_initial' === $field ? 'is_final' : 'is_initial';
        if ($data && isset($data['id'])) {
            $id = (int) $data['id'];
            // $field and $disableField come from the allowlist above.
            $connection->createQueryBuilder()
                ->update($this->table)
                ->set($field, '1')
                ->set($disableField, '0')
                ->where('id = :id')
                ->setParameter('id', $id)
                ->executeStatement()
            ;

            $this->deleteOtherRows($field, (int) $courseId, $sessionId, $id);
        } else {
            $params = [
                'exercise_id' => $exerciseId,
                'c_id' => $courseId,
                'session_id' => $sessionId,
                $field => 1,
                $disableField => 0,
            ];
            $id = Database::insert($this->table, $params);

            $this->deleteOtherRows($field, (int) $courseId, $sessionId, (int) $id);
        }
    }

    /**
     * Removes the other rows flagged with $field (allowlisted by setOption()) in the course and session.
     */
    private function deleteOtherRows(string $field, int $courseId, $sessionId, int $id): void
    {
        Database::getManager()->getConnection()->createQueryBuilder()
            ->delete($this->table)
            ->where($field.' = 1')
            ->andWhere('c_id = :courseId')
            ->andWhere('session_id = :sessionId')
            ->andWhere('id <> :id')
            ->setParameter('courseId', $courseId)
            ->setParameter('sessionId', $sessionId)
            ->setParameter('id', $id)
            ->executeStatement()
        ;
    }

    private function getCourseExercise($courseId, $sessionId, $isInitial, $isFinal)
    {
        return Database::getManager()->getConnection()->createQueryBuilder()
            ->select('*')
            ->from($this->table)
            ->where('c_id = :courseId')
            ->andWhere('session_id = :sessionId')
            ->andWhere('is_initial = :isInitial')
            ->andWhere('is_final = :isFinal')
            ->setParameter('courseId', (int) $courseId)
            ->setParameter('sessionId', (int) $sessionId)
            ->setParameter('isInitial', $isInitial ? 1 : 0)
            ->setParameter('isFinal', $isFinal ? 1 : 0)
            ->executeQuery()
            ->fetchAssociative()
        ;
    }
}
