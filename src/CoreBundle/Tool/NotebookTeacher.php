<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Tool;

class NotebookTeacher extends AbstractPlugin
{
    public function getTitle(): string
    {
        return 'NotebookTeacher';
    }

    public function getLink(): string
    {
        return '/plugin/NotebookTeacher/start.php';
    }

    public function getIcon(): string
    {
        return 'mdi-note-edit';
    }

    public function getTitleToShow(): string
    {
        return 'Teacher notes';
    }
}
