<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp\Dto;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * What it takes to create a course document, for a caller that is not an HTTP
 * request: the MCP tools and the AI media storage service.
 *
 * They used to express this by building a synthetic Request and invoking
 * CreateDocumentFileAction as a callable with its ten resolved dependencies. That
 * made the request a parameter bag -- thirteen untyped strings -- and coupled the
 * callers to the controller's signature, which is how both of them ended up
 * passing AiDisclosureHelper where a CidReqHelper was expected.
 */
final readonly class CourseDocumentInput
{
    /**
     * @param ?string $title            null lets the uploaded file name decide
     * @param string  $filetype         file, folder or link
     * @param ?string $contentFile      inline content, for a document created from a string
     * @param ?string $language         an isocode, or an /api/languages/{id} IRI; empty falls back to the course's
     * @param string  $fileExistsOption rename, overwrite or nothing
     */
    public function __construct(
        public ?string $title = null,
        public string $filetype = 'file',
        public string $comment = '',
        public int $parentResourceNodeId = 0,
        public int $visibility = ResourceLink::VISIBILITY_PUBLISHED,
        public ?string $contentFile = null,
        public ?string $contentFileExtension = null,
        public ?string $contentFileMimeType = null,
        public ?UploadedFile $uploadFile = null,
        public ?string $language = null,
        public string $fileExistsOption = 'rename',
        public bool $aiAssisted = false,
    ) {}
}
