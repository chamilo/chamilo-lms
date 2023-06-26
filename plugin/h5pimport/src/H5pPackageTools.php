<?php

namespace Chamilo\PluginBundle\H5pImport\H5pImporter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\PluginBundle\Entity\H5pImport\H5pImport;
use Chamilo\PluginBundle\Entity\H5pImport\H5pImportLibrary;
use Database;
use Symfony\Component\Filesystem\Filesystem;

class H5pPackageTools
{
    /**
     * Help read JSON from the archive
     *
     * @param string $file
     * @param bool $assoc
     * @return mixed JSON content if valid or FALSE for invalid
     */
    public static function getJson(string $file, bool $assoc = false)
    {

        $fs = new Filesystem();
        $json = false;

        if ($fs->exists($file)) {

            $contents = '';
            $fileContent = fopen($file, "r");
            while (!feof($fileContent)) {
                $contents .= fread($fileContent, 2);
            }

            // Decode the data
            $json = json_decode($contents, $assoc);
            if ($json === null) {
                // JSON cannot be decoded or the recursion limit has been reached.
                return false;
            }
        }

        return $json;
    }

    /**
     * Checks the integrity of an H5P package by verifying the existence of libraries
     * and moves them to the "libraries" directory.
     *
     * @param object $h5pJson      The H5P JSON object.
     * @param string $extractedDir The path to the extracted directory.
     *
     * @return bool True if the package integrity is valid, false otherwise.
     */
    public static function checkPackageIntegrity(object $h5pJson, string $extractedDir): bool
    {
        $filesystem = new Filesystem();
        $h5pDir = dirname($extractedDir);
        $sharedLibrariesDir = $h5pDir . '/libraries';

        // Get the list of preloaded dependencies
        $preloadedDependencies = $h5pJson->preloadedDependencies;

        // Check the existence of each library in the extracted directory
        foreach ($preloadedDependencies as $dependency) {
            $libraryName = $dependency->machineName;
            $majorVersion = $dependency->majorVersion;
            $minorVersion = $dependency->minorVersion;

            $libraryFolderName = $libraryName . '-' . $majorVersion . '.' . $minorVersion;
            $libraryPath = $extractedDir . '/' . $libraryFolderName;

            // Check if the library folder exists
            if (!$filesystem->exists($libraryPath)) {
                return false;
            }

            // Move the entire folder to the "libraries" directory
            $targetPath = $sharedLibrariesDir . '/' . $libraryFolderName;

            $filesystem->rename($libraryPath, $targetPath, true);

        }

        return true;
    }

    /**
     * Stores the H5P package information in the database.
     *
     * @param string $packagePath The path to the H5P package file.
     * @param object $h5pJson The parsed H5P JSON object.
     * @param Course $course The course entity related to the package.
     * @param Session|null $session The session entity related to the package.
     * @return void
     */
    public static function storeH5pPackage(
        string $packagePath,
        object $h5pJson,
        Course $course,
        Session $session = null
    ) {
        $entityManager = Database::getManager();
        $h5pDir = dirname($packagePath);
        $sharedLibrariesDir = $h5pDir . '/libraries';

        $mainLibraryName = $h5pJson->mainLibrary;

        $h5pImport = new H5pImport();
        $h5pImport->setName($h5pJson->title);
        $h5pImport->setPath($packagePath);
        $h5pImport->setCourse($course);
        $h5pImport->setSession($session);
        $entityManager->persist($h5pImport);

        $libraries = $h5pJson->preloadedDependencies;

        foreach ($libraries as $libraryData) {
            $library = $entityManager
                ->getRepository(H5pImportLibrary::class)
                ->findOneBy(
                    [
                        'machineName' => $libraryData->machineName,
                        'majorVersion' => $libraryData->majorVersion,
                        'minorVersion' => $libraryData->minorVersion,
                        'course' => $course,
                    ]
                );

            if (null === $library) {
                $library = new H5pImportLibrary();
                $library->setMachineName($libraryData->machineName);
                $library->setMajorVersion($libraryData->majorVersion);
                $library->setMinorVersion($libraryData->minorVersion);
                $library->setLibraryPath($sharedLibrariesDir);
                $library->setCourse($course);
                $entityManager->persist($library);
                $entityManager->flush();
            }

            $h5pImport->addLibraries($library);
            if ($mainLibraryName === $libraryData->machineName) {
                $h5pImport->setMainLibrary($library);
            }
            $entityManager->persist($h5pImport);
            $entityManager->flush();

        }
    }

    /**
     * Deletes an H5P package from the database and the disk.
     *
     * @param H5pImport $h5pImport  The H5P import entity representing the package to delete.
     *
     * @return bool True if the package was successfully deleted, false otherwise.
     */
    public static function deleteH5pPackage(H5pImport $h5pImport): bool
    {
        $packagePath = $h5pImport->getPath();
        $entityManager = Database::getManager();
        $entityManager->remove($h5pImport);
        $entityManager->flush();

        $filesystem = new Filesystem();

        if ($filesystem->exists($packagePath)) {
            try {
                $filesystem->remove($packagePath);
            } catch (\Exception $e) {
                return false;
            }
        }

        return true;
    }
}
