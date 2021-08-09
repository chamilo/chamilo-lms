<?php

/* For licensing terms, see /license.txt */

/**
 * This script cleans old records in (mostly) track_e_* and saves them to a
 * separate track_e_*_[YYYY] table, with the name of the year in which this
 * script is run as a suffix.
 * Because we don't know the column that reflects the date of the record in the
 * corresponding table (it varies from table to table), we use the time of
 * running the script as the suffix to the archive table.
 * This script uses the primary key to calculate the ID up to which we want to
 * archive records, so if many records have previously been deleted, the
 * number of moved records might be considerable inferior to $archiveNumber.
 * This script does the following:
 * - run through the list of programmed tables to archive
 * - check if the archive table exists, otherwise create it
 * - gets the lower ID of the original table, adds the threshold and proceeds
 *   to move all first [$threshold] records to an archive table
 * - removes the moved records from the original table
 * - writes a log in a local file (see $logFile below)
 * To fine-tune it, change the $tables, $threshold and $archiveNumber variables
 * below.
 *
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
if (php_sapi_name() !== 'cli') {
    exit("This script must be run from the command-line. Goodbye.\n");
}
require __DIR__.'/../../main/inc/global.inc.php';
ini_set('max_execution_time', 0);
$logFile = __DIR__.'/archive_track_tables_records.log';
// List of tables to be "archived"
$tables = [
    'track_e_downloads',
];
// The threshold (number under which nothing must be done). Defaults to 10M
$threshold = 10000000;
// The number of records to be archived
$archiveNumber = 5000000;
// Browse tables and archive some data
foreach ($tables as $table) {
    $tableArchive = $table.'_'.date('Y');
    if (!checkAndCreateTable($tableArchive, $table)) {
        exit("Could not create table $tableArchive. Please check your database user has the CREATE TABLE permission.\n");
    }
    $sql = "SELECT count(*) FROM $table";
    $res = Database::query($sql);
    $row = Database::fetch_row($res);
    file_put_contents(
        $logFile,
        "Checking current records in $table (".date('Y-m-d H:i:s').")\n===\n",
        FILE_APPEND
    );
    // If more than 10M registers, move the 10M first registers to a, history table
    if ($row[0] > $threshold) {
        $pk = getPKFromTable($table);
        $sql = "SELECT min($pk) FROM $table";
        $res = Database::query($sql);
        $row = Database::fetch_row($res);
        $min = $row[0];
        $max = $min + $archiveNumber;
        $sql = "INSERT INTO $tableArchive SELECT * FROM $table WHERE $pk >= $min AND $pk < $max";
        file_put_contents(
            $logFile,
            "Moving rows $min to ".($max - 1)." to $tableArchive. Starting at ".date('Y-m-d H:i:s')."\n",
            FILE_APPEND
        );
        $res = Database::query($sql);
        file_put_contents(
            $logFile,
            "Done moving at ".date('Y-m-d H:i:s')."\n",
            FILE_APPEND
        );
        if ($res !== false) {
            $sql = "DELETE FROM $table WHERE $pk < $max";
            file_put_contents(
                $logFile,
                "Deleting rows < $max from $table. Starting at ".date('Y-m-d H:i:s')."\n",
                FILE_APPEND
            );
            $res = Database::query($sql);
            file_put_contents(
                $logFile,
                "Done cleaning up at ".date('Y-m-d H:i:s')."\n",
                FILE_APPEND
            );
        } else {
            file_put_contents(
                $logFile,
                "There was an issue copying the rows to $tableArchive\n[Query: $sql ].\n",
                FILE_APPEND
            );
        }
        file_put_contents(
            $logFile,
            "Table process finished at ".date('Y-m-d H:i:s').".\n",
            FILE_APPEND
        );
    } else {
        file_put_contents(
            $logFile,
            "There are no more than $threshold transactions in $table table. No action taken.\n",
            FILE_APPEND
        );
    }
}
file_put_contents(
    $logFile,
    "Archiving finished at ".date('Y-m-d H:i:s').". No action taken.\n",
    FILE_APPEND
);

/**
 * Returns the name of the primary key column for the given table.
 *
 * @param string $table The name of the table
 *
 * @return string The column name of the primary key
 */
function getPKFromTable($table)
{
    $sql = "SELECT k.column_name
        FROM information_schema.table_constraints t
        JOIN information_schema.key_column_usage k
        USING(constraint_name, table_schema, table_name)
        WHERE t.constraint_type = 'PRIMARY KEY'
        AND t.table_schema='".api_get_configuration_value('main_database')."'
        AND t.table_name='$table'";
    $res = Database::query($sql);
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);

        return $row['column_name'];
    }

    return '';
}

/**
 * Check a table exists and create it if it doesn't.
 *
 * @param string $table         The table to check/create
 * @param string $templateTable The model from which this table should be created
 *
 * @return bool True if it exists *or* it could be created, false if there was an error at create time
 */
function checkAndCreateTable($table, $templateTable)
{
    $sqlCheck = "SHOW TABLES LIKE '$table'";
    $res = Database::query($sqlCheck);
    if (Database::num_rows($res) > 0) {
        // The table already exists
        return true;
    } else {
        $sqlCreate = "CREATE TABLE $table like $templateTable";
        $res = Database::query($sqlCreate);

        return $res;
    }
}
