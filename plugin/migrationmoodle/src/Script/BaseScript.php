<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Script;

use Chamilo\PluginBundle\MigrationMoodle\Traits\MapTrait\MapTrait;

/**
 * Class BaseScript.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Script
 */
abstract class BaseScript
{
    use MapTrait;

    /**
     * BaseScript constructor.
     */
    public function __construct()
    {
        $this->calledClass = get_called_class();
    }

    public function run()
    {
        \Database::insert(
            'plugin_migrationmoodle_task',
            ['name' => $this->getTaskName()]
        );

        $this->process();
    }

    abstract public function process();

    /**
     * @param int $userId
     *
     * @return bool
     */
    protected function isLoadedUser($userId)
    {
        return $this->isLoadedId($userId, 'users_task');
    }

    /**
     * @param int $lpId
     *
     * @return bool
     */
    protected function isMigratedLearningPath($lpId)
    {
        return $this->isLoadedId($lpId, 'course_sections_task');
    }

    /**
     * @param int $scormId
     *
     * @return bool
     */
    protected function isMigratedScorm($scormId)
    {
        return $this->isLoadedId($scormId, 'course_modules_scorm_task');
    }

    /**
     * @param string $message
     */
    protected function showMessage($message)
    {
        echo '['.date(\DateTime::ATOM)."]\t$message".PHP_EOL;
    }

    /**
     * @param int    $id
     * @param string $taskName
     *
     * @return bool
     */
    private function isLoadedId($id, $taskName)
    {
        $row = \Database::fetch_assoc(
            \Database::query(
                "SELECT COUNT(pmi.id) AS nbr
                    FROM plugin_migrationmoodle_item pmi
                    INNER JOIN plugin_migrationmoodle_task pmt ON pmi.task_id = pmt.id
                    WHERE pmt.name = '$taskName' AND pmi.loaded_id = $id"
            )
        );

        return $row['nbr'] > 0;
    }
}
