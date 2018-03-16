<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

/**
 * Checks total platform size.
 *
 * @param bool $debug
 *
 * @return bool
 */
function isTotalPortalSizeBiggerThanLimit($debug = true)
{
    $sizeLimit = api_get_configuration_value('hosting_total_size_limit');
    if (empty($sizeLimit)) {
        return true;
    }

    $updateFile = true;
    $file = api_get_path(SYS_COURSE_PATH).'hosting_total_size.php';

    // Default data
    $hostingData = [
        'frequency' => 86400,
    ];

    $log = null;

    // Check if file exists and if it is updated
    if (file_exists($file)) {
        $hostingDataFromFile = require $file;

        // Check time() is UTC
        if (isset($hostingDataFromFile['updated_at']) &&
            isset($hostingDataFromFile['frequency']) &&
            isset($hostingDataFromFile['size'])
        ) {
            $hostingData = $hostingDataFromFile;

            $time = $hostingData['updated_at'] + $hostingData['frequency'];
            $diff = $time - time();

            if ($time > time()) {
                $log .= "You need to wait $diff seconds to update the file \n";
                $updateFile = false;
            }
        }
    }

    // Now get values for total portal size
    $log .= "Frequency loaded: ".$hostingData['frequency']."\n";

    if ($updateFile) {
        $log .= "Updating total size ... \n";
        $totalSize = calculateTotalPortalSize($debug);
        $log .= "Total size calculated: $totalSize \n";

        $hostingData['updated_at'] = time();
        $hostingData['size'] = $totalSize;

        $writer = new Zend\Config\Writer\PhpArray();
        $phpCode = $writer->toString($hostingData);
        file_put_contents($file, $phpCode);
        $log .= "File saved in $file \n";
    } else {
        $log .= "Total size not updated \n";
        $totalSize = $hostingData['size'];
    }

    $result = true;

    if ($totalSize > $sizeLimit) {
        $log .= "Current total size of $totalSize MB is bigger than limit: $sizeLimit MB \n";
        $result = false;
    }

    if ($debug) {
        echo $log;
    }

    return $result;
}

/**
 * @param bool $debug
 *
 * @return int total size in MB
 */
function calculateTotalPortalSize($debug)
{
    $table = Database::get_course_table(TABLE_DOCUMENT);
    // Documents
    $sql = "SELECT SUM(size) total FROM $table
            WHERE filetype = 'file' AND c_id <> ''";
    $result = Database::query($sql);
    $row = Database::fetch_array($result, 'ASSOC');
    $totalSize = $row['total'];

    if ($debug) {
        echo "Total size in table $table ".(round($totalSize / 1024))." MB \n";
    }

    $table = Database::get_course_table(TABLE_FORUM_ATTACHMENT);
    $sql = "SELECT SUM(size) total FROM $table WHERE c_id <> ''";
    $result = Database::query($sql);
    $row = Database::fetch_array($result, 'ASSOC');
    $subTotal = $row['total'];
    $totalSize += $subTotal;
    if ($debug) {
        echo "Total size in table $table ".(round($subTotal / 1024))." MB \n";
    }

    $totalSize = $totalSize / 1024;

    return $totalSize;
}

isTotalPortalSizeBiggerThanLimit(true);
