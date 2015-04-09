<?php
/* For licensing terms, see /license.txt */
/**
 * Detects visible _DELETED_ visible files
 */
exit;

require __DIR__.'/../../main/inc/global.inc.php';
api_protect_admin_script();

// Define origin and destination courses' code
$debug = true;

$document = Database::get_course_table(TABLE_DOCUMENT);
$itemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);

$sql = "SELECT i.* FROM $document d
       INNER JOIN $itemProperty i
       ON (d.c_id = i.c_id AND i.ref = d.id AND d.session_id = i.session_id)
       WHERE
            d.path LIKE '%_DELETED_%' AND
            i.visibility IN (1, 0) AND
            tool = 'document'
    ";
$result = Database::query($sql);
$docs = Database::store_result($result);
if (!empty($docs)) {
    foreach ($docs as $doc) {

        $courseId = $doc['c_id'];
        $ref = $doc['ref'];
        $sessionId = $doc['id_session'];

        $sql = "UPDATE $itemProperty
                SET visibility = 2
                WHERE
                    c_id = $courseId AND
                    session_id = $sessionId AND
                    ref = $ref AND
                    tool =  'document'
                ";
        var_dump($sql);
        //Database::query($sql);

    }
}

