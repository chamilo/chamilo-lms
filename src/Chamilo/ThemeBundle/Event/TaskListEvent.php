<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\Event;

use Chamilo\ThemeBundle\Model\TaskInterface;

/**
 * Class TaskListEvent.
 *
 * @package Chamilo\ThemeBundle\Event
 */
class TaskListEvent extends ThemeEvent
{
    protected $tasks = [];
    protected $total = 0;

    /**
     * @return array
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * @return $this
     */
    public function addTask(TaskInterface $taskInterface)
    {
        $this->tasks[] = $taskInterface;

        return $this;
    }

    /**
     * @param int $total
     *
     * @return $this
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        return $this->total == 0 ? sizeof($this->tasks) : $this->total;
    }
}
