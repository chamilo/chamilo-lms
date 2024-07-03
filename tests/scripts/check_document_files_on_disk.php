<?php
/* For licensing terms, see /license.txt */

/**
 * This script fixes the paths from document table on disk.
 */

exit;

require_once '../../main/inc/global.inc.php';

if (empty($argv[1])) {
    die("You have to add a course code to check a course or 'ALL' for all courses as first parameter");
}

if (empty($argv[2]) || (!empty($argv[2]) && !(in_array($argv[2], ['true', 'false'])))) {
    die("You have to add 'true' or 'false' as second parameter to remove Rows");
}

$courseCode = $argv[1];
$removeFileNotFound = ('true' === $argv[2]);

echo checkDocumentFilesOnDisk($courseCode, $removeFileNotFound);

/**
 * It checks the files of documents.
 *
 * @param $courseCode
 *
 * @return string
 */
function checkDocumentFilesOnDisk($courseCode, $removeFileNotFound)
{
    $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
    $tableDocument = Database::get_course_table(TABLE_DOCUMENT);

    $sql = "SELECT id, code, title, directory FROM $tableCourse";
    if (!empty($courseCode) && 'all' !== strtolower($courseCode)) {
        $sql .= " WHERE code = '$courseCode'";
    }
    $rs = Database::query($sql);
    $log = '';
    if (Database::num_rows($rs) > 0) {
        while ($course = Database::fetch_array($rs, 'ASSOC')) {
            $sourcePath = api_get_path(SYS_COURSE_PATH).$course['directory'].'/document';
            $log .= "Checking c_document for the course : {$course['title']} ({$course['code']})".PHP_EOL;
            $rsDoc = Database::query("SELECT path FROM $tableDocument WHERE filetype = 'file' AND c_id = {$course['id']}");
            if (Database::num_rows($rsDoc) > 0) {
                while ($doc = Database::fetch_array($rsDoc, 'ASSOC')) {

                    // If document file path doesn't exist on disk
                    if (!existDocFileOnDisk($sourcePath, $doc['path'])) {
                        // It searches the current path
                        $searchFile = basename($doc['path']);
                        $currentDocPaths = getDocFilePath($sourcePath, $searchFile);

                        $log .= "The document path {$doc['path']} is not on disk, searching the file $searchFile on other path ".PHP_EOL;
                        if (!empty($currentDocPaths)) {
                            // The file exists in other paths
                            foreach ($currentDocPaths as $currentPath) {
                                $log .= "Found on : {$currentPath}".PHP_EOL;
                                $sourceDir = dirname($sourcePath.$doc['path']);
                                // it checks if the current path is stored in document table
                                if (isPathInDocumentTable($currentPath, $course['id'], $sourcePath)) {
                                    // to copy the file to the new doc path
                                    if (!file_exists($sourceDir)) {
                                        mkdir($sourceDir, api_get_permissions_for_new_directories(), true);
                                    }
                                    if (copy($currentPath, $sourcePath.$doc['path'])) {
                                        $log .= "CASE 1 - Checking document table, it exists as document path, so it is copied to the new document path {$doc['path']}".PHP_EOL;
                                    }
                                } else {
                                    // to move the file to the new doc path
                                    if (!file_exists($sourceDir)) {
                                        mkdir($sourceDir, api_get_permissions_for_new_directories(), true);
                                    }
                                    if (rename($currentPath, $sourcePath.$doc['path'])) {
                                        $log .= "CASE 2 - Checking document table, it doesn't exist in other path, so it is moved to the new doc path {$doc['path']}".PHP_EOL;
                                    }
                                }
                            }
                        } else {
                            // to remove the path from document table
                            if ($removeFileNotFound) {
                                if (deletePathInDocumentTable($doc['path'], $course['id'])) {
                                    $log .= "CASE 3 - The document path {$doc['path']} doesn't exist on disk, so it is removed the row".PHP_EOL;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    return $log;
}

/**
 * Delete the row path from document table.
 *
 * @param $docPath
 * @param $cid
 *
 * @return int
 */
function deletePathInDocumentTable($docPath, $cid)
{
    $tableDocument = Database::get_course_table(TABLE_DOCUMENT);
    $rs = Database::query("DELETE FROM $tableDocument WHERE c_id = $cid AND path = '$docPath'");

    return Database::affected_rows($rs);
}

/**
 * It checks if filepath is in document table.
 *
 * @param $filePath
 * @param $cid
 * @param $sourcePath
 *
 * @return bool
 */
function isPathInDocumentTable($filePath, $cid, $sourcePath)
{
    $tableDocument = Database::get_course_table(TABLE_DOCUMENT);

    $docPath = str_replace($sourcePath, '', $filePath);

    $sql = "SELECT path
            FROM $tableDocument
            WHERE c_id = $cid
              AND path = '$docPath'";
    $rs = Database::query($sql);

    return Database::num_rows($rs) > 0;
}

/**
 * It checks if document row path exists on disk.
 *
 * @param $sourcePath
 * @param $documentPath
 *
 * @return bool
 */
function existDocFileOnDisk($sourcePath, $documentPath)
{
    $filePath = $sourcePath.$documentPath;
    $exists = file_exists($filePath);

    return $exists;
}

/**
 * It gets the current path from document.
 *
 * @param $sourcePath
 * @param $searchFile
 *
 * @return array
 */
function getDocFilePath($sourcePath, $searchFile)
{
    if (!is_dir($sourcePath)) {
        return false;
    }

    $directory = new RecursiveDirectoryIterator($sourcePath);
    $currentPaths = [];
    foreach (new RecursiveIteratorIterator($directory) as $filename => $item) {
        if (strtolower($item->getFilename()) === strtolower($searchFile)) {
            $currentPaths[] = $filename;
        }
    }

    return $currentPaths;
}
