<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Dto\Gradebook;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * The course context GradebookContextResolver resolves once per request, and
 * every Gradebook and reporting endpoint reads from.
 *
 * canManage answers "may edit here", which is false in the student view. A read
 * gate must not use it: see IsAllowedToEditHelper in CLAUDE.md.
 *
 * Excluded from the service container: entities and scalars are nothing
 * autowiring can supply.
 */
#[Exclude]
final readonly class GradebookContext
{
    public function __construct(
        public Course $course,
        public ?Session $session,
        public int $groupId,
        public ?GradebookCategory $rootCategory,
        public User $user,
        public bool $canManage,
    ) {}
}
