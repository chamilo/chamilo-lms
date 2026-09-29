<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\CourseReporting;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class CourseReportingContext
{
    public function __construct(
        public Course $course,
        public ?Session $session,
        public int $groupId,
        public User $currentUser,
        public bool $isAdministrator,
        public bool $isTeacher,
        public bool $isHumanResourcesManager,
        public bool $showEmailAddresses,
        public bool $hideCharts,
        public bool $useMaximumLearningPathProgress,
        public bool $hideSessionList,
        public bool $allowMessageTracking,
        /**
         * @var int[]
         */
        public array $configuredExerciseIds,
        /**
         * @var int[]
         */
        public array $hiddenColumnIndexes,
        /**
         * @var string[]
         */
        public array $defaultExtraFieldVariables,
    ) {}

    public function courseId(): int
    {
        return (int) $this->course->getId();
    }

    public function sessionId(): int
    {
        return (int) ($this->session?->getId() ?? 0);
    }

    public function courseResourceNodeId(): int
    {
        return (int) ($this->course->getResourceNode()?->getId() ?? 0);
    }
}
