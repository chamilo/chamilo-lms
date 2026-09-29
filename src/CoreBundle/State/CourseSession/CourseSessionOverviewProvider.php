<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseSession;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseSession\CourseSessionOverview;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<CourseSessionOverview>
 */
final readonly class CourseSessionOverviewProvider implements ProviderInterface
{
    public function __construct(
        private CourseSessionManager $manager,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseSessionOverview
    {
        $sessionId = (int) ($uriVariables['sessionId'] ?? 0);
        if ($sessionId <= 0) {
            throw new BadRequestHttpException('A valid session id is required.');
        }

        $data = $this->manager->getOverviewData($sessionId);
        $resource = new CourseSessionOverview();
        $resource->session = (array) $data['session'];
        $resource->courses = (array) $data['courses'];
        $resource->users = (array) $data['users'];
        $resource->canManageUsers = !empty($data['canManageUsers']);
        $resource->canManageUserCourses = !empty($data['canManageUserCourses']);

        return $resource;
    }
}
