<?php
/* For licensing terms, see /license.txt */
/**
 * This script removes duplicated documents.
 * It identifies duplicate documents by title, path and size, and
 * makes sure no usage is associated with the duplicated document, and
 * that the duplicated document is not used in a learning path.
 * A duplicated document will generally match the following criteria:
 * - same size as the original
 * - same path and title with the addition of a _%d (int) suffix to the basename (before the file extension) (unlikely to ever be > 9, so one digit is enough)
 * - same course, same session (otherwise considered a different file, a voluntary copy)
 * - each have entries in c_item_property because it was created legitimately
 * Possible duplicates can be found with a query like:
 * SELECT id, size, title, path FROM c_document WHERE c_id = 470 AND path like '%\__.%' ORDER BY path, title;
 * This script should be located inside the tests/scripts/ folder to work.
 * It can be run more than one time as it will only ever affect duplicate
 * documents.
 * If you have a very large number of documents, we recommend you temporarily
 * comment out the api_item_property_update() calls in
 * DocumentManager::deleteDocumentFromDb.
 * Chances are there is not even a registry of those documents there in the
 * first place (they were probably duplicated through a short process) and
 * this is where most of the time is spent during deletion.
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
exit; //remove this line to execute from the command line
use ChamiloSession as Session;

ini_set('memory_limit', '256M');

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';

$tests = [];

// Debug shows more output and only does a fake run
$debug = false;
$_user['user_id'] = 1;
Session::write('_user', $_user);

echo "[".time()."] Querying courses\n";
$sql = "SELECT id, code FROM course order by id";

$resCourse = Database::query($sql);
if ($resCourse === false) {
    exit('Could not find any course'.PHP_EOL);
}
$countCourses = Database::num_rows($resCourse);
echo "[".time()."] Found $countCourses courses".PHP_EOL;

// Check all c_document.id = c_document.iid, otherwise cancel
$sql = "SELECT iid FROM c_document WHERE id != iid";
$res = Database::query($sql);
if (Database::num_rows($res) > 0) {
    echo "We have detected that some c_document.id do not match c_document.iid.".PHP_EOL;
    echo "This can lead to serious inconsistencies in the execution of this script.".PHP_EOL;
    echo "Please fix this issue first, then try this script again.".PHP_EOL;
    exit;
}

$duplicateDocsCount = 0;
$originalDocsCount = 0;
$deletedDocsCount = 0;
$docsWithTracking = 0;
$docsInLP = 0;
$deletedDocsSize = 0;

// Search for duplicate tests, by looking for tests that have the exact same
// title in the same course
echo "[".time()."] Iterating on courses: ";
while ($course = Database::fetch_assoc($resCourse)) {
    $course['real_id'] = $course['id'];
    if ($debug) {
        echo PHP_EOL."Course ".$course['id'].'..'.PHP_EOL;
    }
    $_course = api_get_course_info_by_id($course['id']);
    $courseDir = $_course['directory'].'/document';
    $sysCoursePath = api_get_path(SYS_COURSE_PATH);
    $baseWorkDir = $sysCoursePath.$courseDir;
    // We consider duplicates in sessions to be highly improbable, as course
    // copies that could have been broken are essentially made on base courses.
    $sql2 = "SELECT iid, title, path, size FROM c_document
            WHERE c_id = ".$course['id']."
            AND (session_id = 0 OR session_id IS NULL)
            AND filetype = 'file'
            ORDER BY path desc, title, iid";
    $res2 = Database::query($sql2);
    if ($res2 === false) {
        die("Error querying docs in course code ".$course['code'].": ".Database::error($res2)."\n");
    }

    // Extract the root filename, which is not always the one without _%d at the end.
    // Sometimes, the original has been deleted but there are still replicates.
    $lastOriginalDocPath = '';
    $lastOriginalDocId = 0;
    $lastOriginalDocSize = 0;
    if (Database::num_rows($res2) > 0) {
        while ($doc = Database::fetch_assoc($res2)) {
            if ($debug) {
                echo $doc['path'].PHP_EOL;
            }
            $matches = [];
            $guessedOriginal = '';
            $notOriginal = preg_match('/(.*)_\d(\.[a-zA-Z0-9]{1,4})$/', $doc['path'], $matches);

            if ($notOriginal) {
                if ($debug) {
                    echo "This looks like a copy".PHP_EOL;
                }
                $guessedOriginal = $matches[1].$matches[2];
                if ($debug) {
                    echo "The original would be ".$guessedOriginal.PHP_EOL;
                }
            } else {
                if ($debug) {
                    echo "This looks like an original. Recording and moving on...".PHP_EOL;
                }
                $lastOriginalDocPath = $doc['path'];
                $lastOriginalDocId = $doc['iid'];
                $lastOriginalDocSize = $doc['size'];
                $originalDocsCount++;
                // Move directly to the next item
                continue;
            }

            if ($lastOriginalDocPath != $guessedOriginal) {
                if ($debug) {
                    echo "The guessed original filename is different from the original, or the original could not be found. Skipping...".PHP_EOL;
                }
                // The title is different -> moving on to another doc, but
                // recording new doc's details just in case
                $lastOriginalDocPath = $doc['path'];
                $lastOriginalDocId = $doc['iid'];
                $lastOriginalDocSize = $doc['size'];
                $originalDocsCount++;
            } else {
                // A likely duplicate...
                // Only bother if the doc's internal ID is higher than the
                // last original doc ID, which means this (duplicate) test
                // has been created *after* the original.
                if ($lastOriginalDocId < $doc['iid'] && $lastOriginalDocSize == $doc['size']) {
                    if ($debug) {
                        echo "This doc has been created after the original and has the same size. Good.".PHP_EOL;
                    }
                    // This duplicate document could have been seen or downloaded already,
                    // but this is not considered critical when deciding whether to clean
                    // it or not.
                    // It is, however, essential to make sure this duplicate document is
                    // not used from inside a learning path.
                    $sql4 = "SELECT lp_id FROM c_lp_item
                    WHERE c_id = ".$course['id']."
                    AND item_type = 'document' AND ref = ".$doc['iid'];
                    $res4 = Database::query($sql4);
                    if (0 === Database::num_rows($res4)) {
                        if ($debug) {
                            echo "The file is not used in any LP".PHP_EOL;
                        } else {
                            DocumentManager::delete_document($_course, $doc['path'], $baseWorkDir, null, $doc['iid']);
                            DocumentManager::purgeDocument($doc['iid'], $_course);
                        }
                        if ($debug) {
                            echo $doc['iid'].' deleted.'.PHP_EOL;
                        }
                        $deletedDocsCount++;
                        $deletedDocsSize += $doc['size'];
                    } else {
                        if ($debug) {
                            echo "This document is used from a learning path. Deletion cancelled.".PHP_EOL;
                        }
                    }
                }
                $duplicateDocsCount++;
            }
        } // end while on c_document
    }
} // end while on course

$sizeInMB = (int) $deletedDocsSize / (1024*1024);
echo "[".time()."] Found $originalDocsCount original docs and $duplicateDocsCount duplicate docs...".PHP_EOL;
echo "Of these duplicates, $docsInLP were included in learning paths.".PHP_EOL;
echo "Deleted $deletedDocsCount ($duplicateDocsCount - $docsInLP) docs for a total of $sizeInMB MB.".PHP_EOL;
