<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\Assignment;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use ZipArchive;

final class AssignmentFileUploadValidator
{
    public function validate(UploadedFile $file): void
    {
        if (($file->getSize() ?? 0) <= 0) {
            throw new UnprocessableEntityHttpException('The selected file is empty.');
        }

        if ('zip' !== strtolower($file->getClientOriginalExtension())) {
            return;
        }

        if (!class_exists(ZipArchive::class)) {
            throw new UnprocessableEntityHttpException('ZIP uploads are unavailable on this server.');
        }

        $archive = new ZipArchive();
        if (true !== $archive->open($file->getPathname(), ZipArchive::CHECKCONS)) {
            throw new UnprocessableEntityHttpException('The selected ZIP file is invalid.');
        }

        try {
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $entry = $archive->statIndex($index);
                if (!\is_array($entry)) {
                    throw new UnprocessableEntityHttpException('The selected ZIP file is invalid.');
                }

                if (!str_ends_with((string) $entry['name'], '/') && (int) $entry['size'] > 0) {
                    return;
                }
            }

            throw new UnprocessableEntityHttpException('The selected ZIP file contains no non-empty files.');
        } finally {
            $archive->close();
        }
    }
}
