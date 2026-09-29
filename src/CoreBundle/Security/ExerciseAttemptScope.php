<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security;

/**
 * Who is asking for exercise attempts, stated as facts rather than as roles.
 *
 * TrackEExerciseRepository builds its WHERE from this and never reads the security token;
 * ExerciseAttemptScopeFactory is the only place that turns a token into one of these.
 */
final readonly class ExerciseAttemptScope
{
    public function __construct(
        public int $userId,
        public bool $mayFollowAsStudentBoss = false,
        public bool $mayFollowAsDrh = false,
        public bool $mayAdministerSessions = false,
    ) {}
}
