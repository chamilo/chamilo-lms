<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseAverageMetricInSession;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\TrackingStatsHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CourseTrackingMetricStateProvider extends AbstractTrackingStateProvider implements ProviderInterface
{
    public function __construct(
        EntityManagerInterface $entityManager,
        CourseRepository $courseRepository,
        SessionRepository $sessionRepository,
        RequestStack $requestStack,
        Security $security,
        private readonly TrackingStatsHelper $trackingStatsHelper,
    ) {
        parent::__construct(
            $entityManager,
            $courseRepository,
            $sessionRepository,
            $requestStack,
            $security
        );
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object
    {
        $course = $this->getCourseFromQuery();
        $session = $this->getSessionFromQuery();

        $this->denyUnlessCanViewCourse($course);

        return match ($operation->getName()) {
            'tracking_course_average_score_in_session' => $this->provideAverageScore($course, $session),
            'tracking_course_average_progress_in_session' => $this->provideAverageProgress($course, $session),
            default => throw new NotFoundHttpException('Tracking metric not supported.'),
        };
    }

    private function provideAverageScore(
        Course $course,
        ?Session $session
    ): CourseAverageMetricInSession {
        $data = $this->trackingStatsHelper->getCourseAverageScore($course, $session);

        return new CourseAverageMetricInSession(
            (float) $data['avg'],
            (int) $data['participants'],
        );
    }

    private function provideAverageProgress(
        Course $course,
        ?Session $session
    ): CourseAverageMetricInSession {
        $data = $this->trackingStatsHelper->getCourseAverageProgress($course, $session);

        return new CourseAverageMetricInSession(
            (float) $data['avg'],
            (int) $data['participants'],
        );
    }
}
