<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseSession;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseSession\CourseSessionUserCourses;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<CourseSessionUserCourses>
 */
final readonly class CourseSessionUserCoursesProvider implements ProviderInterface
{
    public function __construct(
        private CourseSessionManager $manager,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseSessionUserCourses
    {
        $sessionId = (int) ($uriVariables['sessionId'] ?? 0);
        $userId = (int) ($uriVariables['userId'] ?? 0);
        if ($sessionId <= 0 || $userId <= 0) {
            throw new BadRequestHttpException('Valid session and user ids are required.');
        }

        $data = $this->manager->getUserCoursesData($sessionId, $userId);
        $resource = new CourseSessionUserCourses();
        $resource->sessionId = (int) $data['sessionId'];
        $resource->sessionTitle = (string) $data['sessionTitle'];
        $resource->user = (array) $data['user'];
        $resource->courses = (array) $data['courses'];

        return $resource;
    }
}
