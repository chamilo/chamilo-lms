<?php
/* For licensing terms, see /license.txt */
/**
 * This script removes attachments of old messages.
 * Messages sent through announcements in Chamilo 1.11.* will replicate
 * the attachment as many times as there are recipients of the message.
 * This is highly inefficient. This script allows you to remove (from disk)
 * such attachments from the recipients, without removing it from the originals.
 * This script should be located inside the tests/scripts/ folder to work
 * @author Yannick Warnier <yannick.warnier@beeznest.com> - Cleanup and debug
 */
exit;
require __DIR__.'/../../main/inc/global.inc.php';
$simulate = false;
$userBasePath = api_get_path(SYS_UPLOAD_PATH).'users/';

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

echo "Processing...".PHP_EOL.PHP_EOL;

echo "[".time()."] Querying messages\n";
$sql = "SELECT m.id mid, m.msg_status, m.user_sender_id,
          user_receiver_id, ma.id maid, ma.path, m.send_date
        FROM message m, message_attachment ma
        WHERE m.id = ma.message_id
          AND m.msg_status IN (0,1,3,4)
          AND m.send_date > '$from 00:00:00' AND m.send_date <= '$until 23:59:59'";

$res = Database::query($sql);

if ($res === false) {
    die("Error querying messages\n");
}

$countMessages = Database::num_rows($res);
$sql = "SELECT count(*) nbr FROM message WHERE msg_status IN (0,1,3,4)";
$resc = Database::query($sql);
if ($resc === false) {
    die("Error querying total messages\n");
}
$countAllMessages = Database::result($resc, 0, 'nbr');
echo "[".time()."]"
    ." Found $countMessages messages between $from and $until with attachments on a total of $countAllMessages messages."."\n";

$sqlDeleteAttach = "DELETE FROM message_attachment WHERE id = ";
/**
 * Locate and destroy the expired message attachments
 */
$totalSize = 0;
$senderMessagesList = []; //list of [sender_id][date_of_message][] with all iids
while ($message = Database::fetch_assoc($res)) {
    switch ($message['msg_status']) {
        case '4':
            // This message is in status "outbox", meaning it's in the
            // sender's outbox.
            //echo "Message ".$message['mid']." is in user ".$message['user_sender_id']." outbox".PHP_EOL;
            $usi = $message['user_sender_id'];
            $filePath = substr($usi, 0, 1).'/'.$usi.'/message_attachments/'.$message['path'];
            if (file_exists($userBasePath.$filePath)) {
                //echo "  File found".PHP_EOL;
                // Build the $senderMessagesList array to later scan it and remove all those but one
                if (!isset($senderMessagesList[$message['user_sender_id']])) {
                    $senderMessagesList[$message['user_sender_id']] = [];
                }
                if (!isset($senderMessagesList[$message['user_sender_id']][$message['send_date']])) {
                    $senderMessagesList[$message['user_sender_id']][$message['send_date']] = [];
                }
                if (!isset($senderMessagesList[$message['user_sender_id']][$message['send_date']][$message['maid']])) {
                    $senderMessagesList[$message['user_sender_id']][$message['send_date']][$message['maid']] = $userBasePath.$filePath;
                }
            }
            break;
        case '0':
        case '1':
            //echo "Message ".$message['mid']." is in user ".$message['user_receiver_id']." inbox".PHP_EOL;
            $usi = $message['user_receiver_id'];
            $filePath = substr($usi, 0, 1).'/'.$usi.'/message_attachments/'.$message['path'];
            if (file_exists($userBasePath.$filePath)) {
                //echo "  File found".PHP_EOL;
                $totalSize += filesize($userBasePath.$filePath);
                if ($simulate == false) {
                    exec('rm -f '.$userBasePath.$filePath);
                    $deleteResult = Database::query($sqlDeleteAttach.$message['maid']);
                } else {
                    echo "Would delete ".$userBasePath.$filePath.PHP_EOL;
                    echo "Query: ".$sqlDeleteAttach.$message['maid'].PHP_EOL;
                }
            }
            break;
        case '3':
            //echo "Message ".$message['mid']." can be in two different folders".PHP_EOL;
            $usi = $message['user_receiver_id'];
            $filePath = substr($usi, 0, 1).'/'.$usi.'/message_attachments/'.$message['path'];
            // Check if this file is on the receiver's side
            if (file_exists($userBasePath.$filePath)) {
                //echo "  File found in receiver's path".PHP_EOL;
                $totalSize += filesize($userBasePath.$filePath);
                if ($simulate == false) {
                    exec('rm -f '.$userBasePath.$filePath);
                    $deleteResult = Database::query($sqlDeleteAttach.$message['maid']);
                } else {
                    echo "Would delete ".$userBasePath.$filePath.PHP_EOL;
                    echo "Query: ".$sqlDeleteAttach.$message['maid'].PHP_EOL;
                }
            } else {
                // Not on the receiver's side, so must be on the sender's side
                $usi = $message['user_sender_id'];
                $filePath = substr($usi, 0, 1).'/'.$usi.'/message_attachments/'.$message['path'];
                if (file_exists($userBasePath.$filePath)) {
                    //echo "  File found in sender's path".PHP_EOL;
                    $totalSize += filesize($userBasePath.$filePath);
                    if ($simulate == false) {
                        // Even though we would normally not delete sender files
                        // indiscriminately, status=3 means the message was
                        // deleted by the user, so... no mercy!
                        exec('rm -f '.$userBasePath.$filePath);
                        $deleteResult = Database::query($sqlDeleteAttach.$message['maid']);
                    } else {
                        echo "Would delete ".$userBasePath.$filePath.PHP_EOL;
                        echo "Query: ".$sqlDeleteAttach.$message['maid'].PHP_EOL;
                    }
                }
            }
            break;
    }
}
// Now go through the messages from senders (i.e. in the outbox of the sender)
// and delete all attachments except one: the one from the latest message.
//echo "Checking sender messages".PHP_EOL;
foreach ($senderMessagesList as $usi => $userArray) {
    foreach ($userArray as $date => $attachmentsList) {
        $itemsCount = count($attachmentsList);
        $i = 0;
        foreach ($attachmentsList as $attachId => $path) {
            if ($i+1 == $itemsCount) {
                // we're at the last element, so don't delete it
                //echo "Not deleting attachment $attachId".PHP_EOL;
            } else {
                // not the last, so delete
                $totalSize += filesize($path);
                if ($simulate == false) {
                    exec('rm -f '.$path);
                    Database::query($sqlDeleteAttach.$attachId);
                } else {
                    echo "Would delete $path".PHP_EOL;
                    echo "Query: ".$sqlDeleteAttach.$attachId.PHP_EOL;
                }
            }
            $i++;
        }
    }
}

echo "[".time()."] ".($simulate ? "Would delete" : "Deleted")
    ." attachments from $countMessages messages between $from and $until on a total of $countAllMessages"
    ." messages, for a total estimated size of "
    .round($totalSize / (1024 * 1024))." MB.".PHP_EOL;
exit;
