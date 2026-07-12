<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use Chamilo\CourseBundle\Entity\CWikiConf;

final readonly class WikiAssignmentFeedbackResolver
{
    public function resolve(CWikiConf $configuration, int $progress): string
    {
        $feedbacks = [
            [$configuration->getFprogress1(), $configuration->getFeedback1()],
            [$configuration->getFprogress2(), $configuration->getFeedback2()],
            [$configuration->getFprogress3(), $configuration->getFeedback3()],
        ];

        foreach ($feedbacks as [$storedProgress, $feedback]) {
            if ('' === trim((string) $storedProgress)) {
                continue;
            }

            if ($progress === $this->normalizeStoredProgress((string) $storedProgress)
                && '' !== trim((string) $feedback)
            ) {
                return trim((string) $feedback);
            }
        }

        return '';
    }

    public function normalizeStoredProgress(string $progress): int
    {
        $value = (int) trim($progress);

        if ($value <= 0) {
            return 0;
        }

        return $value <= 10 ? $value * 10 : min(100, $value);
    }

    public function serializeProgress(int $progress): string
    {
        if ($progress <= 0) {
            return '';
        }

        return (string) intdiv($progress, 10);
    }
}
