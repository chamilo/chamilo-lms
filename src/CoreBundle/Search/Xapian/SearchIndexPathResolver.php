<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Search\Xapian;

use RuntimeException;

/**
 * Resolves and prepares the filesystem path where the Xapian index is stored.
 */
final class SearchIndexPathResolver
{
    public function __construct(
        private string $indexDir,
    ) {}

    /**
     * Returns the absolute directory where the Xapian index is stored.
     * Ensures that the directory exists on disk.
     */
    public function getIndexDir(): string
    {
        $this->ensureIndexDirectoryExists();

        return $this->indexDir;
    }

    /**
     * Ensures that the index directory exists and is writable.
     *
     * @throws RuntimeException when the directory cannot be created
     */
    public function ensureIndexDirectoryExists(): void
    {
        if (is_dir($this->indexDir)) {
            return;
        }

        if (!@mkdir($this->indexDir, 0775, true) && !is_dir($this->indexDir)) {
            throw new RuntimeException(\sprintf('Unable to create Xapian index directory: %s', $this->indexDir));
        }
    }
}
