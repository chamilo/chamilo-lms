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

    abstract function process();

    /**
     * @param string $message
     */
    protected function showMessage($message)
    {
        echo '['.date(\DateTime::ATOM)."]\t$message".PHP_EOL;
    }
}
