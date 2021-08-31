<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationAssignment;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;
use Chamilo\CourseBundle\Entity\CStudentPublicationCorrection;

class Assignment extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'student_publication';
    }

    public function getLink(): string
    {
        return '/main/work/work.php';
    }

    public function getIcon(): string
    {
        return 'mdi-inbox-full';
    }

    public function getCategory(): string
    {
        return 'interaction';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'student_publications' => CStudentPublication::class,
            'student_publications_assignments' => CStudentPublicationAssignment::class,
            'student_publications_comments' => CStudentPublicationComment::class,
            'student_publications_corrections' => CStudentPublicationCorrection::class,
        ];
    }
}
