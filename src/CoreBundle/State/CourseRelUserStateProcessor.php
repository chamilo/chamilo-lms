<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Helpers\CourseCatalogueHelper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProcessorInterface<CourseRelUser, CourseRelUser|void>
 */
final class CourseRelUserStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly CourseCatalogueHelper $courseCatalogueHelper,
        private readonly Security $security,
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): ?CourseRelUser
    {
        if (!$operation instanceof Post) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        \assert($data instanceof CourseRelUser);

        if (!$this->security->isGranted('ROLE_ADMIN')
            && !$this->courseCatalogueHelper->isCourseInPublicCatalogue($data->getCourse())
        ) {
            throw new AccessDeniedHttpException('Course is not available in the public catalogue.');
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
