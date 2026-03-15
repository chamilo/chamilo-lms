<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\UserAvgLpProgressInCourseAndSession;
use Chamilo\CoreBundle\ApiResource\UserGradebookResultInCourseAndSession;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\TrackingStatsHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class UserTrackingMetricStateProvider extends AbstractTrackingStateProvider implements ProviderInterface
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
        $user = $this->getUserFromQuery();
        $course = $this->getCourseFromQuery();
        $session = $this->getSessionFromQuery();

        $this->denyUnlessCanReadUserTracking($user, $course, $session);

        return match ($operation->getName()) {
            'tracking_user_avg_lp_progress_in_course_and_session' => $this->provideAvgLpProgress($user, $course, $session),
            'tracking_user_gradebook_result_in_course_and_session' => $this->provideGradebookResult($user, $course, $session),
            default => throw new NotFoundHttpException('Tracking metric not supported.'),
        };
    }

    private function provideAvgLpProgress(
        User $user,
        Course $course,
        ?Session $session
    ): UserAvgLpProgressInCourseAndSession {
        $data = $this->trackingStatsHelper->getUserAvgLpProgress($user, $course, $session);

        return new UserAvgLpProgressInCourseAndSession(
            (float) $data['avg'],
            (int) $data['count'],
        );
    }

    private function provideGradebookResult(
        User $user,
        Course $course,
        ?Session $session
    ): UserGradebookResultInCourseAndSession {
        $data = $this->trackingStatsHelper->getUserGradebookGlobal($user, $course, $session);

        return new UserGradebookResultInCourseAndSession(
            (float) $data['score'],
            (float) $data['max'],
            (float) $data['percentage'],
        );
    }
}
