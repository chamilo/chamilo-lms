<?php
/**
 * TaskListEvent.php
 * avanzu-admin
 * Date: 23.02.14.
 */

namespace Chamilo\ThemeBundle\Event;

use Chamilo\ThemeBundle\Model\TaskInterface;

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
        return 0 == $this->total ? count($this->tasks) : $this->total;
    }
}
