<?php

namespace PhpCoveralls\Component\File;

/**
 * Path utils.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class Path
{
    /**
     * Return whether the path is relative path.
     *
     * @param string $path path
     *
     * @return bool true if the path is relative path, false otherwise
     */
    public function isRelativePath($path)
    {
        if (strlen($path) === 0) {
            return true;
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return !preg_match('/^[a-z]+\:\\\\/i', $path);
        }

        return strpos($path, DIRECTORY_SEPARATOR) !== 0;
    }

    /**
     * Cat file path.
     *
     * @param string $path    file path
     * @param string $rootDir absolute path to project root directory
     *
     * @return false|string absolute path
     */
    public function toAbsolutePath($path, $rootDir)
    {
        if (!is_string($path)) {
            return false;
        }

        if ($this->isRelativePath($path)) {
            return $rootDir . DIRECTORY_SEPARATOR . $path;
        }

        return $path;
    }

    /**
     * Return real file path.
     *
     * @param string $path    file path
     * @param string $rootDir absolute path to project root directory
     *
     * @return false|string real path string if the path string is passed and real path exists, false otherwise
     */
    public function getRealPath($path, $rootDir)
    {
        if (!is_string($path)) {
            return false;
        }

        if ($this->isRelativePath($path)) {
            return realpath($rootDir . DIRECTORY_SEPARATOR . $path);
        }

        return realpath($path);
    }

    /**
     * Return real directory path.
     *
     * @param string $path    path
     * @param string $rootDir absolute path to project root directory
     *
     * @return false|string real directory path string if the path string is passed and real directory exists, false otherwise
     */
    public function getRealDir($path, $rootDir)
    {
        if (!is_string($path)) {
            return false;
        }

        if ($this->isRelativePath($path)) {
            return realpath($rootDir . DIRECTORY_SEPARATOR . dirname($path));
        }

        return realpath(dirname($path));
    }

    /**
     * Return real file path to write.
     *
     * @param string $path    file path
     * @param string $rootDir absolute path to project root directory
     *
     * @return false|string real file path string if the parent directory exists, false otherwise
     */
    public function getRealWritingFilePath($path, $rootDir)
    {
        $realDir = $this->getRealDir($path, $rootDir);

        if (!is_string($realDir)) {
            return false;
        }

        return $realDir . DIRECTORY_SEPARATOR . basename($path);
    }

    /**
     * Return whether the real path exists.
     *
     * @param bool|string $realpath real path
     *
     * @return bool true if the real path exists, false otherwise
     */
    public function isRealPathExist($realpath)
    {
        return $realpath !== false && file_exists($realpath);
    }

    /**
     * Return whether the real file path exists.
     *
     * @param bool|string $realpath real file path
     *
     * @return bool true if the real file path exists, false otherwise
     */
    public function isRealFileExist($realpath)
    {
        return $this->isRealPathExist($realpath) && is_file($realpath);
    }

    /**
     * Return whether the real file path is readable.
     *
     * @param bool|string $realpath real file path
     *
     * @return bool true if the real file path is readable, false otherwise
     */
    public function isRealFileReadable($realpath)
    {
        return $this->isRealFileExist($realpath) && is_readable($realpath);
    }

    /**
     * Return whether the real file path is writable.
     *
     * @param bool|string $realpath real file path
     *
     * @return bool true if the real file path is writable, false otherwise
     */
    public function isRealFileWritable($realpath)
    {
        return $this->isRealFileExist($realpath) && is_writable($realpath);
    }

    /**
     * Return whether the real directory exists.
     *
     * @param bool|string $realpath real directory path
     *
     * @return bool true if the real directory exists, false otherwise
     */
    public function isRealDirExist($realpath)
    {
        return $this->isRealPathExist($realpath) && is_dir($realpath);
    }

    /**
     * Return whether the real directory is writable.
     *
     * @param bool|string $realpath real directory path
     *
     * @return bool true if the real directory is writable, false otherwise
     */
    public function isRealDirWritable($realpath)
    {
        return $this->isRealDirExist($realpath) && is_writable($realpath);
    }
}
