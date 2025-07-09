<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use League\Flysystem\FilesystemOperator;

final class FileHelper
{
    public function __construct(
        private readonly FilesystemOperator $resourceFilesystem
    ) {}

    /**
     * Writes contents to a file.
     *
     * Equivalent to file_put_contents().
     */
    public function write(string $path, string $contents): void
    {
        $this->resourceFilesystem->write($path, $contents);
    }

    /**
     * Reads the contents of a file as a string.
     *
     * Equivalent to file_get_contents().
     */
    public function read(string $path): ?string
    {
        return $this->resourceFilesystem->read($path);
    }

    /**
     * Deletes a file.
     *
     * Equivalent to unlink().
     */
    public function delete(string $path): void
    {
        $this->resourceFilesystem->delete($path);
    }

    /**
     * Checks if a file exists.
     *
     * Equivalent to file_exists().
     */
    public function exists(string $path): bool
    {
        return $this->resourceFilesystem->fileExists($path);
    }

    /**
     * Opens a file for reading as a stream resource.
     *
     * Equivalent to fopen() in read mode.
     */
    public function readStream(string $path)
    {
        return $this->resourceFilesystem->readStream($path);
    }

    /**
     * Writes a stream resource to a file.
     *
     * Equivalent to fwrite() when working with streams.
     */
    public function writeStream(string $path, $stream): void
    {
        $this->resourceFilesystem->writeStream($path, $stream);
    }

    /**
     * Moves an uploaded file into Flysystem storage.
     *
     * Equivalent to UploadedFile->move().
     *
     * @param string $tempPath Local temporary path of the uploaded file.
     * @param string $targetPath Target path in the filesystem.
     */
    public function moveUploadedFile(string $tempPath, string $targetPath): void
    {
        $stream = fopen($tempPath, 'r');
        $this->writeStream($targetPath, $stream);
        fclose($stream);

        unlink($tempPath);
    }

    /**
     * Outputs the contents of a file directly to the browser.
     *
     * Equivalent to readfile().
     */
    public function streamToOutput(string $path): void
    {
        $stream = $this->readStream($path);
        fpassthru($stream);
        fclose($stream);
    }

    /**
     * Creates a directory.
     *
     * Equivalent to mkdir().
     */
    public function createDirectory(string $path): void
    {
        $this->resourceFilesystem->createDirectory($path);
    }

    /**
     * Lists files and directories under a given path as a flat array.
     *
     * Equivalent to scandir(), opendir(), readdir().
     *
     * @param string $path Path to list (empty string = root).
     * @param bool $deep true = recursive listing, false = shallow listing.
     *
     * @return array
     */
    public function listContents(string $path = '', bool $deep = false): array
    {
        return $this->resourceFilesystem
            ->listContents($path, $deep)
            ->toArray();
    }

    /**
     * Gets the size of a file in bytes.
     *
     * Equivalent to filesize().
     */
    public function fileSize(string $path): int
    {
        return $this->resourceFilesystem->fileSize($path);
    }

    /**
     * Gets the last modified timestamp of a file.
     *
     * Equivalent to filemtime().
     */
    public function lastModified(string $path): int
    {
        return $this->resourceFilesystem->lastModified($path);
    }

    /**
     * Deletes an entire directory and its contents.
     *
     * Equivalent to rmdir() or a recursive delete.
     */
    public function deleteDirectory(string $path): void
    {
        $this->resourceFilesystem->deleteDirectory($path);
    }

    /**
     * Copies a file to a new location.
     *
     * Equivalent to copy().
     */
    public function copy(string $source, string $destination): void
    {
        $this->resourceFilesystem->copy($source, $destination);
    }

    /**
     * Moves or renames a file.
     *
     * Equivalent to rename() or a file move.
     */
    public function move(string $source, string $destination): void
    {
        $this->resourceFilesystem->move($source, $destination);
    }
}
