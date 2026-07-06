<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\CourseLinkSessionHelper;
use Chamilo\CoreBundle\Helpers\CToolIntroHelper;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Resolves the active course-tool introduction for the current course/session
 * context (replacing the legacy CourseController::getToolIntro action).
 *
 * Returns the session-specific intro when it exists, otherwise the base intro
 * flagged as createInSession, or a transient empty one when none exists. The
 * intro text is rewritten so its course-context URLs target the current session.
 *
 * @template-implements ProviderInterface<CToolIntro>
 */
final readonly class CToolIntroCurrentProvider implements ProviderInterface
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private CToolIntroHelper $toolIntroHelper,
        private CourseLinkSessionHelper $courseLinkSessionHelper,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CToolIntro
    {
        $course = $this->cidReqHelper->getDoctrineCourseEntity();
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('Course not found.');
        }

        $session = $this->cidReqHelper->getDoctrineSessionEntity();

        $toolName = trim((string) ($context['filters']['tool'] ?? 'course_homepage'));
        if ('' === $toolName) {
            $toolName = 'course_homepage';
        }

        $intro = $this->toolIntroHelper->getCurrentIntro($course, $toolName, $session);

        $intro->setIntroText(
            $this->courseLinkSessionHelper->rewriteSessionForCourse($intro->getIntroText(), (int) $course->getId())
        );

        return $intro;
    }
}
