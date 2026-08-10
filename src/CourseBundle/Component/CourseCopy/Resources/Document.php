<?php

declare(strict_types=1);
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

class Document extends Resource
{
    public string $path;
    public string $full_path;
    public ?string $comment = null;
    public string $file_type;
    public string $filetype;
    public string $size;
    public string $title;

    /**
     * ResourceLink visibility for the document in the export context.
     * Uses ResourceLink constants: 0=draft, 1=pending, 2=published.
     * Null means "unknown / keep destination default".
     */
    public ?int $visibility = null;

    /**
     * Source ResourceNode UUID (for rewriting modern /r/document/files/{uuid}/view URLs).
     */
    public ?string $resource_node_uuid = null;

    public function __construct(
        $id,
        $fullPath,
        $comment,
        $title,
        $file_type,
        $size,
        ?int $visibility = null,
        ?string $resourceNodeUuid = null
    ) {
        parent::__construct($id, RESOURCE_DOCUMENT);

        $clean = ltrim((string) $fullPath, '/');
        $this->path = 'document/'.$clean;
        $this->full_path = $this->path;
        $this->comment = $comment ?? '';
        $this->title = (string) $title;
        $this->file_type = (string) $file_type;
        $this->filetype = (string) $file_type;
        $this->size = (string) $size;
        $this->visibility = $visibility;
        $this->resource_node_uuid = $resourceNodeUuid;
    }

    public function show(): void
    {
        parent::show();
        echo preg_replace('@^document@', '', $this->path);
        if (!empty($this->title) && !str_contains($this->path, $this->title)) {
            echo ' - '.$this->title;
        }
    }
}
