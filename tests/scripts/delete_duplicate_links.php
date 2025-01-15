<?php
/* For licensing terms, see /license.txt */
/**
 * This script removes duplicated links.
 * It identifies duplicate links by URL,
 * makes sure no usage is associated with the duplicated link, and
 * that the duplicated link is not used in a learning path.
 * A duplicated link will generally match the following criteria:
 * - same URL field as the original
 * - same title
 * - same category_id
 * - same on_homepage field
 * - same target
 * - same course, same session (otherwise considered a different link, a voluntary copy)
 * - each have entries in c_item_property because it was created legitimately
 * Possible duplicates can be found with a query like:
 * SELECT iid, c_id, session_id, url, title, description, target, on_homepage FROM c_link WHERE c_id = 470 AND url like '%\__.%' ORDER BY url, title;
 * This script should be located inside the tests/scripts/ folder to work.
 * It can be run more than one time as it will only ever affect duplicate
 * links.
 * If you have a very large number of links, we recommend you temporarily
 * comment out the api_item_property_update() calls in
 * Link::deletelinkcategory() (which deletes a link *or* a category).
 * Chances are there is not even a registry of those links there in the
 * first place (they were probably duplicated through a short/broken process) and
 * this is where most of the time is spent during deletion.
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 * @author Christian Fasanando <christian.fasanando@beeznest.com>
 */
exit; //remove this line to execute from the command line

use ChamiloSession as Session;

ini_set('memory_limit', '256M');

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';

$debug = true;
$_user['user_id'] = 1;
Session::write('_user', $_user);

echo "[" . time() . "] Querying courses\n";
$sql = "SELECT id, code FROM course ORDER BY id";

$resCourse = Database::query($sql);
if ($resCourse === false) {
    exit("Could not find any course\n");
}
$countCourses = Database::num_rows($resCourse);
echo "[" . time() . "] Found $countCourses courses\n";

$duplicatesCount = 0;
$originalsCount = 0;
$deletedCount = 0;
$itemsInLP = 0;

// Iterate through each course
while ($course = Database::fetch_assoc($resCourse)) {
    if (empty($course['id'])) {
        continue; // Skip invalid course IDs
    }
    if ($debug) {
        echo "\n-= Course ".$course['id']." (".$course['code'].") =-\n----\n";
    }

    $sql2 = "SELECT iid, url, title, description, category_id, on_homepage, target, session_id
             FROM c_link
             WHERE c_id = " . $course['id'] . "
             AND (session_id = 0 OR session_id IS NULL)
             ORDER BY url, title, iid";
    $res2 = Database::query($sql2);

    if ($res2 === false) {
        die("Error querying links in course code " . $course['code'] . "\n");
    }

    $links = [];
    while ($item = Database::fetch_assoc($res2)) {
        $links[] = $item;
    }

    // Track processed duplicates to avoid redundant operations
    $processedDuplicates = [];

    foreach ($links as $key => $original) {
        $originalsCount++;
        // Make sure we don't use a just-deleted link as original
        if (in_array($original['iid'], $processedDuplicates)) {
            continue;
        }
        foreach ($links as $key2 => $duplicate) {
            if ($debug) {
                echo "Checking potential duplicate link ".$duplicate['iid']." against original ".$original['iid']."\n";
            }
            if (
                $key !== $key2 &&
                !in_array($duplicate['iid'], $processedDuplicates) &&
                $original['url'] === $duplicate['url'] &&
                $original['title'] === $duplicate['title'] &&
                $original['description'] === $duplicate['description'] &&
                $original['category_id'] === $duplicate['category_id'] &&
                $original['on_homepage'] === $duplicate['on_homepage'] &&
                $original['target'] === $duplicate['target'] &&
                $original['session_id'] === $duplicate['session_id'] &&
                $original['iid'] < $duplicate['iid']
            ) {
                $duplicatesCount++;
                if ($debug) {
                    echo "\n[".date('Y-m-d h:i:s')."]\nDuplicate found in Course ID: " . $course['id'] . "\n";
                    echo "Original IID=" . $original['iid'] . ", Duplicate IID=" . $duplicate['iid'] . " ($duplicatesCount)\n";
                }

                // Check if duplicate exists in c_lp_item
                $checkSql = "SELECT COUNT(*) as count FROM c_lp_item
                             WHERE ref = " . $duplicate['iid'] . "
                             AND c_id = " . $course['id'] . "
                             AND item_type = 'link'";
                $checkResult = Database::query($checkSql);
                $row = Database::fetch_assoc($checkResult);

                if ($row['count'] > 0) {
                    $itemsInLP++;
                    if ($debug) {
                        echo "Duplicate in learning path: IID=" . $duplicate['iid'] . " (Original IID=" . $original['iid'] . ")\n";
                    }
                    continue; // Skip duplicates in learning paths
                }

                // Delete the duplicate
                Link::deletelinkcategory($duplicate['iid'], 'link', $course['id'], true);
                $deletedCount++;
                $processedDuplicates[] = $duplicate['iid']; // Mark as processed
                if ($debug) {
                    echo "Deleted Duplicate IID=" . $duplicate['iid'] . "\n";
                }
            }
        }
    }
    if ($debug) {
	    echo "Ending course ".$course['id']."\n\n";
    }
}

// Summary
if ($debug) {
    echo "\nSummary:\n";
    echo "- Total duplicates detected: $duplicatesCount\n";
    echo "- Duplicates ignored (in learning paths): $itemsInLP\n";
    echo "- Duplicates deleted: $deletedCount\n";
    echo "[".time()."] Process complete.\n";
}
