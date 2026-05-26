<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\UserCertificateInCourseAndSession;
use Chamilo\CoreBundle\Helpers\TrackingStatsHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProviderInterface<UserCertificateInCourseAndSession>
 */
final class UserCertificatesInCourseAndSessionStateProvider extends AbstractTrackingStateProvider implements ProviderInterface
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

    /**
     * @return list<UserCertificateInCourseAndSession>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->getUserFromQuery();
        $course = $this->getCourseFromQuery();
        $session = $this->getSessionFromQuery();

        $this->denyUnlessCanReadUserTracking($user, $course, $session);

        $rows = $this->trackingStatsHelper->getUserCertificates($user, $course, $session);

        return array_values(array_map(
            static fn (array $row): UserCertificateInCourseAndSession => new UserCertificateInCourseAndSession(
                (int) $row['id'],
                (string) $row['title'],
                (string) $row['issuedAt'],
                isset($row['downloadUrl']) ? (string) $row['downloadUrl'] : null,
            ),
            $rows
        ));
    }
}
