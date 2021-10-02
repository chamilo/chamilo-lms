<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Doctrine\Persistence\ManagerRegistry;

final class CToolIntroRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CToolIntro::class);
    }

    public function updateToolIntro(
        $em,
        $introText,
        $courseId = null,
        $sessionId = null
    ): CToolIntro {
        if (!isset($coursId)) {
            $courseId = api_get_course_int_id();
        }
        if (!isset($sessionId)) {
            $sessionId = api_get_session_id();
        }

        $course = api_get_course_entity($courseId);
        $teacher = api_get_user_entity();

        /** @var CTool $courseTool */
        $courseTool = $course->getTools()->first();

        $toolIntro = (new CToolIntro())
            ->setIntroText($introText)
            ->setCourseTool($courseTool)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($toolIntro);
        $em->flush();

        return $toolIntro;
    }
}
