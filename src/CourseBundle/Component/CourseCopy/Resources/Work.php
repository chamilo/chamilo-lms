<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * Work/Assignment/Student publication backup resource wrapper.
 */
class Work extends Resource
{
    /** Raw backup parameters (id, title, description, url, etc.). */
    public array $params = [];

    /** Plain properties used by legacy restorer helpers (e.g. to_system_encoding). */
    public string  $title = '';
    public string  $description = '';
    public ?string $url = null;

    public function __construct(array $params)
    {
        parent::__construct((int)($params['id'] ?? 0), RESOURCE_WORK);

        $this->params      = $params;
        $this->title       = isset($params['title']) ? (string) $params['title'] : '';
        $this->description = isset($params['description']) ? (string) $params['description'] : '';
        $this->url         = isset($params['url']) && is_string($params['url']) ? $params['url'] : null;
    }

    public function show(): void
    {
        parent::show();
        echo htmlspecialchars($this->title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Convenience accessor for the backup path, if you prefer not to read $url directly.
     */
    public function getBackupPath(): ?string
    {
        return $this->url
            ?? (isset($this->params['url']) && is_string($this->params['url']) ? $this->params['url'] : null);
    }
}
