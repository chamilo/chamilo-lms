<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Traits\MapTrait;

trait MapTrait
{
    /**
     * @var string
     */
    protected $calledClass;

    /**
     * @return string
     */
    protected function getTaskName()
    {
        $name = substr(strrchr($this->calledClass, '\\'), 1);

        return api_camel_case_to_underscore($name);
    }
}
