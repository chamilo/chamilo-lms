<?php
/* For licensing terms, see /license.txt */
/**
 * This script finds duplicated documents to save disk space.
 *
 * It identifies duplicate documents by building an array of MD5 hashes
 * from the app/courses/[CODE]/document/ folders on your system
 * and checking for similar hashes everywhere in the other documents folders.
 *
 * This script should be located inside the tests/scripts/ folder to work
 * on the command line. To run it in a browser, move it to main/inc/ and
 * load it from there.
 * It can be run more than one time as it will only ever affect duplicate
 * documents.
 *
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
exit; //remove this line to execute from the command line

use ChamiloSession as Session;

ini_set('memory_limit', '512M');

require_once __DIR__.'/../../main/inc/global.inc.php';

$html = false;
$htmlEOL = '';
if (PHP_SAPI !== 'cli') {
    $html = true;
    $htmlEOL = '<br />';
    api_protect_admin_script();
    //die('This script can only be executed from the command line');
}

// Debug shows more output
$debug = false;
// Ignore template files
$ignoreTemplates = true;
$webCode = api_get_path(WEB_CODE_PATH).'document/document.php?cidReq=';

if ($html) {
    echo "<html><body>".$htmlEOL;
}
echo "[".time()."] Querying courses$htmlEOL\n";
$sql = "SELECT id, code, directory FROM course order by id";

$resCourse = Database::query($sql);
if ($resCourse === false) {
    exit('Could not find any course'.PHP_EOL);
}
$countCourses = Database::num_rows($resCourse);
echo "[".time()."] Found $countCourses courses$htmlEOL".PHP_EOL;

$md5Hashes = [];
$md5Sizes = [];
$totalDocs = 0;
$totalDocsSize = 0;
$uniqueDocs = 0;
$duplicateDocsCount = 0;
$totalDuplicateDocs = 0;
$courseCodeDirMatch = [];
$sysCoursePath = api_get_path(SYS_COURSE_PATH);

// Search for duplicate tests, by looking for tests that have the exact same
// title in the same course
if ($debug) {
    echo "[".time()."] Iterating on courses...$htmlEOL".PHP_EOL;
}
while ($course = Database::fetch_assoc($resCourse)) {
    if ($debug) {
        echo "Course ".$course['id'].' ('.$course['code'].')..'.$htmlEOL.PHP_EOL;
    }
    $courseCodeDirMatch[$course['code']] = $course['directory'];
    $courseDir = $course['directory'].'/document';
    $baseWorkDir = $sysCoursePath.$courseDir;
    $totalDocs += _scanSubDirs($baseWorkDir, $md5Hashes, $md5Sizes, $course['code'], $ignoreTemplates);
} // end while on course

// Sort array by sizes
arsort($md5Sizes, SORT_NUMERIC);

echo "[".time()."] Here is a list of duplicate files, joined together$htmlEOL".PHP_EOL;
echo "             with a clickable link to each document:$htmlEOL".PHP_EOL;
foreach ($md5Sizes as $hash => $size) {
    $files = $md5Hashes[$hash];
    $countFiles = $realFilesForThisHash = count($files);
    $realFilePath = '';
    $i = 0;
    if ($countFiles > 1) {
        $kSize = floor((int) $files[0]['size']/1024);
        echo ($html ? '<b>' : '').$hash.", size: ".$kSize."KB ($countFiles copies): ".($html ? '</b>' : 0).$htmlEOL.PHP_EOL;
        if ($html) {
            echo "<ul>";
        }
    } else {
        continue;
    }
    foreach ($files as $file) {
        if ($file['link']) {
            // This is a link. Discount from real files
            // and do not count as potential space savings
            $realFilesForThisHash--;
            $totalLinks++;
        } else {
            if ($i != 0) {
                // We don't count the first file as a duplicate
                // nor as potential space savings
                $totalDuplicateDocs++;
                $totalDocsSize += $file['size'];
            }
            if ($html) {
                echo "<li><a target='_blank' href='".$webCode.$file['course']."'>".$file['subpath']."(".$file['course'].")</a></li>".PHP_EOL;
            } else {
                echo $webCode.$file['course'].", as ".$file['path'].$htmlEOL.PHP_EOL;
            }
            $i++;
        }
    }
    if ($html) {
        echo "</ul>";
    }
    if ($realFilesForThisHash > 0) {
        // if at least one of those was really a file, count it as unique
        if ($debug) {
            echo "$hash (".$file['path'].") has $realFilesForThisHash duplicates".PHP_EOL;
        }
        $uniqueDocs++;
    }
}
$duplicateDocsCount = $totalDocs - $uniqueDocs;

$sizeInMB = floor((int) $totalDocsSize / (1024*1024));
echo "[".time()."] Found $totalDocs docs in total (including links).".PHP_EOL;
echo "             Only $uniqueDocs were original files with duplicates.".PHP_EOL;
echo "             $totalDuplicateDocs files remain, as duplicates versions of some of those $uniqueDocs.".PHP_EOL;
echo "             Potential savings: ~$sizeInMB MB.".PHP_EOL;

echo "</body></html>";

/**
 * Scans the given directory and its subdirectories and returns a number of
 * files found
 * @param string $path
 * @param array &$md5Hashes
 * @param string $courseCode
 * @param bool $ignoreTemplates
 * @return int
 */
function _scanSubDirs(string $path, array &$md5Hashes, array &$md5Sizes, string $courseCode, bool $ignoreTemplates = false): int
{
    global $debug;
    $count = 0;
    if (!is_dir($path)) {
        return 0;
    }
    if (substr(basename($path), 0, 1) === '.') {
        // If last path component starts with a '.', ignore
        return 0;
    }
    if ($ignoreTemplates) {
        if (preg_match('#/document/(audio|flash|images|video)#', $path)) {
            if ($debug) {
                echo $path." is part of the template, skipping".PHP_EOL;
            }
            return 0;
        }
    }
    $list = scandir($path);
    foreach ($list as $entry) {
        if (substr($entry, 0, 1) === '.') {
            // If the entry starts with a '.', ignore
            continue;
        }
        $subPath = $path.'/'.$entry;
        if ($ignoreTemplates && preg_match('#/document/index.html$#', $subPath)) {
            continue;
        }
        if (is_dir($subPath)) {
            $count += _scanSubDirs($subPath, $md5Hashes, $md5Sizes, $courseCode, $ignoreTemplates);
        } else {
            $fileMd5 = md5_file($subPath);
            if ($fileMd5 === false) {
                if ($debug) {
                    echo 'Skipping: There was an error calculating MD5 of '.$subPath.PHP_EOL;
                }
                continue;
            }
            $fileSize = filesize($subPath);
            if ($fileSize > 0) {
                if (!isset($md5Hashes[$fileMd5])) {
                    $md5Hashes[$fileMd5] = [];
                }
                $matches = [];
                preg_match('#/document/(.*)#', $subPath, $matches);
                $isLink = is_link($subPath);
                $md5Hashes[$fileMd5][] = [
                    'course' => $courseCode,
                    'link' => $isLink,
                    'path' => $subPath,
                    'size' => $fileSize,
                    'subpath' => $matches[1],
                ];
                $md5Sizes[$fileMd5] = $fileSize;
            } else {
                if ($debug) {
                    echo 'Skipping: Filesize 0 for '.$subPath.PHP_EOL;
                }
                continue;
            }
            $count++;
        }
    }

    return $count;
}
