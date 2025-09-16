<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use InvalidArgumentException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use RuntimeException;
use Throwable;

/**
 * Centralized helper for file operations using Flysystem.
 * All paths are treated as relative to the injected FilesystemOperator root.
 */
final class FileHelper
{
    public function __construct(
        private readonly FilesystemOperator $resourceFilesystem
    ) {}

    /**
     * Normalize a relative path and prevent path traversal.
     * It removes backslashes, null bytes and resolves "." / ".." segments.
     */
    private function normalize(string $path): string
    {
        $path = ltrim(str_replace(['\\', "\0"], ['/', ''], $path), '/');
        $parts = [];
        foreach (explode('/', $path) as $seg) {
            if ('' === $seg || '.' === $seg) {
                continue;
            }
            if ('..' === $seg) {
                array_pop($parts);

                continue;
            }
            $parts[] = $seg;
        }

        return implode('/', $parts);
    }

    /**
     * Writes contents to a file (overwrites if exists).
     * Equivalent to file_put_contents().
     *
     * @throws FilesystemException
     */
    public function write(string $path, string $contents): void
    {
        $path = $this->normalize($path);
        $this->resourceFilesystem->write($path, $contents);
    }

    /**
     * Writes contents replacing existing file if present (delete + write).
     * Useful when an atomic-like replacement is desired.
     *
     * @throws FilesystemException
     */
    public function put(string $path, string $contents): void
    {
        $path = $this->normalize($path);
        if ($this->resourceFilesystem->fileExists($path)) {
            $this->resourceFilesystem->delete($path);
        }
        $this->resourceFilesystem->write($path, $contents);
    }

    /**
     * Reads the contents of a file as a string.
     * Equivalent to file_get_contents().
     *
     * @throws FilesystemException
     */
    public function read(string $path): string
    {
        $path = $this->normalize($path);

        return $this->resourceFilesystem->read($path);
    }

    /**
     * Deletes a file.
     * Equivalent to unlink().
     *
     * @throws FilesystemException
     */
    public function delete(string $path): void
    {
        $path = $this->normalize($path);
        $this->resourceFilesystem->delete($path);
    }

    /**
     * Checks if a file exists.
     * Equivalent to file_exists().
     *
     * @throws FilesystemException
     */
    public function exists(string $path): bool
    {
        $path = $this->normalize($path);

        return $this->resourceFilesystem->fileExists($path);
    }

    /**
     * Opens a file for reading as a stream resource.
     * Equivalent to fopen() in read mode.
     *
     * @return resource
     *
     * @throws FilesystemException
     */
    public function readStream(string $path)
    {
        $path = $this->normalize($path);
        $stream = $this->resourceFilesystem->readStream($path);
        if (!\is_resource($stream)) {
            throw new RuntimeException("Unable to open stream for: {$path}");
        }

        return $stream;
    }

    /**
     * Writes a stream resource to a file.
     * Equivalent to fwrite() when working with streams.
     *
     * @param resource $stream
     *
     * @throws FilesystemException
     */
    public function writeStream(string $path, $stream): void
    {
        $path = $this->normalize($path);
        if (!\is_resource($stream)) {
            throw new InvalidArgumentException('writeStream expects a valid stream resource.');
        }
        $this->resourceFilesystem->writeStream($path, $stream);
    }

    /**
     * Outputs the contents of a file directly to STDOUT.
     * Equivalent to readfile().
     *
     * @throws FilesystemException
     */
    public function streamToOutput(string $path): void
    {
        $path = $this->normalize($path);
        $stream = $this->readStream($path);

        try {
            fpassthru($stream);
        } finally {
            if (\is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * Creates a directory (no-op if it already exists).
     * Equivalent to mkdir().
     *
     * @throws FilesystemException
     */
    public function createDirectory(string $path): void
    {
        $path = $this->normalize($path);
        $this->resourceFilesystem->createDirectory($path);
    }

    /**
     * Checks if a directory exists.
     *
     * @throws FilesystemException
     */
    public function directoryExists(string $path): bool
    {
        $path = $this->normalize($path);

        return $this->resourceFilesystem->directoryExists($path);
    }

    /**
     * Ensures a directory exists (creates if missing).
     *
     * @throws FilesystemException
     */
    public function ensureDirectory(string $path): void
    {
        if (!$this->directoryExists($path)) {
            $this->createDirectory($path);
        }
    }

    /**
     * Lists files and directories under a given path as a flat array.
     * Equivalent to scandir(), opendir(), readdir().
     *
     * @param string $path path to list (empty string = root)
     * @param bool   $deep true = recursive listing, false = shallow listing
     *
     * @return array<int, StorageAttributes>
     *
     * @throws FilesystemException
     */
    public function listContents(string $path = '', bool $deep = false): array
    {
        $path = $this->normalize($path);

        return $this->resourceFilesystem
            ->listContents($path, $deep)
            ->toArray()
        ;
    }

    /**
     * Gets the size of a file in bytes.
     * Equivalent to filesize().
     *
     * @throws FilesystemException
     */
    public function fileSize(string $path): int
    {
        $path = $this->normalize($path);

        return $this->resourceFilesystem->fileSize($path);
    }

    /**
     * Gets the last modified timestamp of a file.
     * Equivalent to filemtime().
     *
     * @throws FilesystemException
     */
    public function lastModified(string $path): int
    {
        $path = $this->normalize($path);

        return $this->resourceFilesystem->lastModified($path);
    }

    /**
     * Deletes an entire directory and its contents.
     * Equivalent to rmdir() or a recursive delete.
     *
     * @throws FilesystemException
     */
    public function deleteDirectory(string $path): void
    {
        $path = $this->normalize($path);
        $this->resourceFilesystem->deleteDirectory($path);
    }

    /**
     * Copies a file to a new location.
     * Equivalent to copy().
     *
     * @throws FilesystemException
     */
    public function copy(string $source, string $destination): void
    {
        $source = $this->normalize($source);
        $destination = $this->normalize($destination);
        $this->resourceFilesystem->copy($source, $destination);
    }

    /**
     * Moves or renames a file.
     * Equivalent to rename() or a file move.
     *
     * @throws FilesystemException
     */
    public function move(string $source, string $destination): void
    {
        $source = $this->normalize($source);
        $destination = $this->normalize($destination);
        $this->resourceFilesystem->move($source, $destination);
    }

    /**
     * Returns the MIME type if the adapter supports it, null otherwise.
     */
    public function mimeType(string $path): ?string
    {
        $path = $this->normalize($path);

        try {
            return $this->resourceFilesystem->mimeType($path);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Moves an uploaded file (local temp) into Flysystem storage.
     * Equivalent to UploadedFile::move() semantics (write + delete original).
     *
     * @param string $tempPath   local temporary path of the uploaded file
     * @param string $targetPath target path in the filesystem
     *
     * @throws FilesystemException
     */
    public function moveUploadedFile(string $tempPath, string $targetPath): void
    {
        $targetPath = $this->normalize($targetPath);

        $stream = @fopen($tempPath, 'r');
        if (!\is_resource($stream)) {
            throw new RuntimeException("Cannot open temporary upload: {$tempPath}");
        }

        try {
            $this->writeStream($targetPath, $stream);
        } finally {
            if (\is_resource($stream)) {
                fclose($stream);
            }
        }

        if (is_file($tempPath)) {
            @unlink($tempPath);
        }
    }
}
