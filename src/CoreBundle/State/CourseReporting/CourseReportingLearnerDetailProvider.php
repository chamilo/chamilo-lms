<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseReporting;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseReporting\CourseReportingLearnerDetail;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingContextResolver;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingQueryService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/** @implements ProviderInterface<CourseReportingLearnerDetail> */
final readonly class CourseReportingLearnerDetailProvider implements ProviderInterface
{
    public function __construct(
        private CourseReportingContextResolver $contextResolver,
        private CourseReportingQueryService $queryService,
        private RequestStack $requestStack,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): CourseReportingLearnerDetail {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $userId = $request->query->getInt('userId');
        if ($userId <= 0) {
            throw new BadRequestHttpException('Missing or invalid learner identifier.');
        }

        $data = $this->queryService->getLearnerDetail(
            $this->contextResolver->resolve(),
            $userId,
            $request->query->getInt('limit', 200)
        );

        $resource = new CourseReportingLearnerDetail();
        $resource->user = $data['user'];
        $resource->downloads = $data['downloads'];
        $resource->forumThreads = $data['forumThreads'];
        $resource->forumPosts = $data['forumPosts'];
        $resource->courseAccess = $data['courseAccess'];
        $resource->resourceAccess = $data['resourceAccess'];

        return $resource;
    }
}
