<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Helpers\CourseHelper;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Deletes a course through CourseHelper::deleteCourse(), the same routine as the course maintenance tool, so
 * subscriptions, session links, tracking rows, class links and the search index are cleaned up instead of leaving
 * orphans (a plain ORM removal would).
 *
 * Documents are never deleted along with the course here: the "exclusive documents" option stays a deliberate choice
 * of the course maintenance screen.
 *
 * @implements ProcessorInterface<Course, null>
 */
final readonly class CourseDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private CourseHelper $courseHelper,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        if (!$data instanceof Course) {
            return null;
        }

        try {
            // On a portal with several URLs the course may only be unlinked from the current one: that is the
            // behavior of the maintenance tool too, and it is not an error.
            $this->courseHelper->deleteCourse($data);
        } catch (RuntimeException $exception) {
            // Raised when something still depends on the course, such as a sequence resource.
            throw new ConflictHttpException($exception->getMessage(), $exception);
        }

        return null;
    }
}
