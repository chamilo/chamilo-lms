<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\EventListener;

use Chamilo\ThemeBundle\Event\TaskListEvent;
use Chamilo\ThemeBundle\Model\TaskModel;

/**
 * Class NavbarTaskListDemoListener.
 *
 * @package Chamilo\ThemeBundle\EventListener
 */
class NavbarTaskListDemoListener
{
    public function onListTasks(TaskListEvent $event)
    {
        foreach ($this->getTasks() as $task) {
            $event->addTask($task);
        }
    }

    protected function getTasks()
    {
        return [
            new TaskModel('make stuff', 30, TaskModel::COLOR_GREEN),
            new TaskModel('make more stuff', 60),
            new TaskModel('some more tasks to do', 10, TaskModel::COLOR_RED),
        ];
    }
}
