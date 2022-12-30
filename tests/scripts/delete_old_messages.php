<?php
/* For licensing terms, see /license.txt */
/**
 * This script removes old messages from the database and disk.
 * It uses parameters in order (all mandatory but the last one).
 * Delete the exit; statement at line 13.
 * This script must be launched as root or another privileged user to be
 * able to delete files on disk (created by the web server)
 * This script should be located inside the tests/scripts/ folder to work
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
exit;
require __DIR__.'/../../main/inc/global.inc.php';
$simulate = false;

// Process script parameters
if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

if (!empty($argv[1]) && $argv[1] == '--from') {
    $from = $argv[2];
}
if (!empty($argv[3]) && $argv[3] == '--until') {
    $until = $argv[4];
}
if (!empty($argv[5]) && $argv[5] == '-s') {
    $simulate = true;
    echo "Simulation mode is enabled".PHP_EOL;
}
if (empty($from) or empty($until)) {
    echo PHP_EOL."Usage: sudo php ".basename(__FILE__)." [options]".PHP_EOL;
    echo "Where [options] can be ".PHP_EOL;
    echo "  --from yyyy-mm-dd    Date from which the content should be removed (e.g. 2017-08-31)".PHP_EOL.PHP_EOL;
    echo "  --until yyyy-mm-dd   Date up to which the content should be removed (e.g. 2020-08-31)".PHP_EOL.PHP_EOL;
    echo "  -s                   (optional) Simulate execution - Do not delete anything, just show numbers".PHP_EOL.PHP_EOL;
    die('Please make sure --from and --until are defined.');
}

echo "About to delete messages from $from to $until".PHP_EOL;
echo deleteMessages($from, $until, $simulate);

/**
 * Delete messages between the given dates and return a log string.
 * @param string $from  'yyyy-mm-dd' format date from which to start deleting
 * @param string $until 'yyyy-mm-dd' format date until which to delete
 * @param bool   $simulate True if we only want to simulate the deletion and collect data
 * @return string
 */
function deleteMessages(string $from, string $until, bool $simulate): string
{
    $log = '';
    $size = 0;
    if ($simulate) {
        $log .= 'Simulation mode ON'.PHP_EOL;
    }
    $messages = getMessagesInDateRange($from, $until);
    $log .= 'Found '.count($messages).' matching messages'.PHP_EOL;
    $count = count($messages);
    foreach ($messages as $message) {
        $log .= "Deleting message ".$message['id'].PHP_EOL;
        $attachment = MessageManager::getAttachment($message['id']);
        if (false !== $attachment) {
            $log .= 'Msg '.$message['id'].' has attachment'.PHP_EOL;
            $size += $attachment['size'];
        }
        if (!$simulate) {
            $log .= 'Deleting '.$message['id'].' for receiver '.$message['user_receiver_id'].PHP_EOL;
            try {
                $result = MessageManager::delete_message_by_user_receiver(
                    $message['user_receiver_id'],
                    $message['id'],
                    true
                );
            } catch (Exception $e) {
                echo $e->getMessage();
                die('Process interrupted because not being able to delete file (permissions issues?) will generate a consistency loss and be difficult to fix. Please use "sudo" or other similar mechanism to ensure the user launching this script is allowed to delete the mentionned file.');
            }
        }
        if ($result || $simulate) {
            $log .= 'Deleted msg id '.str_pad($message['id'], 9, ' ', STR_PAD_LEFT).' in receiver\'s box'.PHP_EOL;
        }
        if (!$simulate) {
            $log .= 'Deleting '.$message['id'].' for sender '.$message['user_sender_id'].PHP_EOL;
            try {
                $result = MessageManager::delete_message_by_user_sender(
                    $message['user_sender_id'],
                    $message['id'],
                    true
                );
            } catch (Exception $e) {
                echo $e->getMessage();
                die('Process interrupted because not being able to delete file (permissions issues?) will generate a consistency loss and be difficult to fix. Please use "sudo" or other similar mechanism to ensure the user launching this script is allowed to delete the mentionned file.');
            }
        }
        if ($result || $simulate) {
            $log .= 'Deleted msg id '.str_pad($message['id'], 9, ' ', STR_PAD_LEFT).' in sender\'s box'.PHP_EOL;
        }
    }
    $log .= 'In total, '.$size.'B were freed together with '.$count.' messages.'.PHP_EOL;

    return $log;
}

/**
 * Get a list of messages IDs between the given dates (sent or received, equally)
 * @param string $from
 * @param string $until
 * @return array
 */
function getMessagesInDateRange(string $from, string $until): array
{
    $messages = [];
    $sql = 'SELECT id, user_sender_id, user_receiver_id, group_id
        FROM '.Database::get_main_table(TABLE_MESSAGE).'
        WHERE send_date > "'.$from.' 00:00:00" AND send_date < "'.$until.' 23:59:59"';
    $res = Database::query($sql);
    if (Database::num_rows($res) > 0) {
        while ($row = Database::fetch_assoc($res)) {
            $messages[] = $row;
        }
    }
    return $messages;
}
