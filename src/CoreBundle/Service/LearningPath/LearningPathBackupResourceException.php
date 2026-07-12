<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\LearningPath;

use RuntimeException;

final class LearningPathBackupResourceException extends RuntimeException
{
    public static function missingDocument(int $documentId, string $documentTitle, string $fileName = ''): self
    {
        $title = trim(strip_tags($documentTitle));
        if ('' === $title) {
            $title = sprintf('Document %d', $documentId);
        }

        $message = sprintf(
            'Document %d "%s" cannot be exported because its stored file is missing.',
            $documentId,
            $title,
        );

        if ('' !== trim($fileName)) {
            $message .= sprintf(' Expected file: %s.', basename($fileName));
        }

        return new self($message);
    }
}
