<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Dto;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Everything BaseResourceFileAction::handleCreateFile() needs to create a
 * resource that carries a file: a document, a personal file, a student
 * publication, a link.
 *
 * It used to read all of this off a Request, which forced callers that are not a
 * request -- the MCP tools, the AI media storage service -- to fabricate one.
 * BaseResourceFileAction::resourceFileInputFromRequest() does that mapping for the
 * HTTP callers now, so the request stays where a request belongs.
 *
 * Two fields distinguish "absent" from "empty", because the old code branched on
 * `$request->request->has(...)`:
 *
 * - $contentFile null means no inline content was sent at all; '' means an empty
 *   HTML editor save, which is a real create.
 * - $language null means the caller said nothing about the language, so the
 *   resource keeps whatever it has; '' means "use the course's".
 */
final readonly class ResourceFileInput
{
    /**
     * @param array<int, array<string, int|string>> $resourceLinkList the link bindings, already forced to the authorized context
     * @param string                                $filetype         file, certificate, folder or link
     */
    public function __construct(
        public string $filetype,
        public int $parentResourceNodeId,
        public ?string $title = null,
        public string $comment = '',
        public array $resourceLinkList = [],
        public ?UploadedFile $uploadFile = null,
        public ?string $contentFile = null,
        public string $contentFileExtension = 'html',
        public string $contentFileMimeType = 'text/html',
        public ?string $language = null,
    ) {}

    public function courseIdFromLinks(): int
    {
        foreach ($this->resourceLinkList as $link) {
            $courseId = (int) ($link['cid'] ?? 0);
            if ($courseId > 0) {
                return $courseId;
            }
        }

        return 0;
    }
}
