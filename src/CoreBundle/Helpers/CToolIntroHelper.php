<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Shared course-tool-introduction logic reused by the read provider and the
 * write processor of the CToolIntro API resource.
 */
final readonly class CToolIntroHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function findCourseTool(Course $course, string $toolName, ?Session $session): ?CTool
    {
        return $this->entityManager->getRepository(CTool::class)->findOneBy([
            'title' => $toolName,
            'course' => $course,
            'session' => $session,
        ]);
    }

    /**
     * Returns the course tool for the given title/context, creating it on the
     * fly when it does not exist yet. Returns null only when the base tool
     * definition is unknown.
     */
    public function getOrCreateCourseTool(Course $course, string $toolName, ?Session $session): ?CTool
    {
        $existing = $this->findCourseTool($course, $toolName, $session);
        if ($existing) {
            return $existing;
        }

        $tool = $this->entityManager->getRepository(Tool::class)->findOneBy(['title' => $toolName]);
        if (!$tool) {
            return null;
        }

        $courseTool = (new CTool())
            ->setTool($tool)
            ->setTitle($toolName)
            ->setCourse($course)
            ->setPosition(1)
            ->setParent($course)
            ->setCreator($course->getCreator())
            ->setSession($session)
            ->addCourseLink($course)
        ;

        $this->entityManager->persist($courseTool);
        $this->entityManager->flush();

        return $courseTool;
    }

    /**
     * Resolves the active introduction for a course tool following the
     * base→session inheritance model (no write side effects):
     *
     * - the session-specific intro when one exists;
     * - otherwise the base intro flagged as createInSession, so editing forks it;
     * - a transient empty CToolIntro when none exists yet.
     */
    public function resolveActiveIntro(Course $course, string $toolName, ?Session $session): CToolIntro
    {
        $introRepo = $this->entityManager->getRepository(CToolIntro::class);

        $baseTool = $this->findCourseTool($course, $toolName, null);
        $baseIntro = $baseTool
            ? $introRepo->findOneBy(['courseTool' => $baseTool], ['iid' => 'DESC'])
            : null;

        $activeIntro = $baseIntro;
        $createInSession = false;

        if (null !== $session) {
            $sessionTool = $this->findCourseTool($course, $toolName, $session);
            $sessionIntro = $sessionTool
                ? $introRepo->findOneBy(['courseTool' => $sessionTool], ['iid' => 'DESC'])
                : null;

            if (null !== $sessionIntro) {
                $activeIntro = $sessionIntro;
            } else {
                $activeIntro = $baseIntro;
                $createInSession = true;
            }
        }

        if (null === $activeIntro) {
            $activeIntro = (new CToolIntro())->setIntroText('');
        }

        return $activeIntro->setCreateInSession($createInSession);
    }
}
