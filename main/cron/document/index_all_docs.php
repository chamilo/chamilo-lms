<?php
/**
 * Script to find a document with a specific title or path in all courses.
 */
/**
 * Code init - comment die() call to enable.
 */
exit();
require '../../inc/global.inc.php';
if (empty($_GET['doc'])) {
    echo "To add a document name to search, add ?doc=abc to the URL\n";
} else {
    echo "Received param ".$_GET['doc']."<br />\n";
}
$allowed_mime_types = DocumentManager::file_get_mime_type(true);
$allowed_extensions = [
    'doc',
    'docx',
    'ppt',
    'pptx',
    'pps',
    'ppsx',
    'xls',
    'xlsx',
    'odt',
    'odp',
    'ods',
    'pdf',
    'txt',
    'rtf',
    'msg',
    'csv',
    'html',
    'htm',
];
$courses_list = CourseManager::get_courses_list();

// Simulating empty specific fields (this is necessary for indexing)
require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';
$specific_fields = get_specific_field_list();
$specific_fields_values = [];
foreach ($specific_fields as $sf) {
    $specific_fields_values[$sf['code']] = '';
}
$td = Database::get_course_table(TABLE_DOCUMENT);

foreach ($courses_list as $course) {
    $course_dir = $course['directory'].'/document';
    $title = Database::escape_string($_GET['doc']);
    $sql = "SELECT id, path, session_id FROM $td WHERE c_id = ".$course['id']." AND path LIKE '%$title%' or title LIKE '%$title%'";
    $res = Database::query($sql);
    if (Database::num_rows($res) > 0) {
        while ($row = Database::fetch_array($res)) {
            $doc_path = api_get_path(SYS_COURSE_PATH).$course_dir.$row['path'];
            $extensions = preg_split("/[\/\\.]/", $doc_path);
            $doc_ext = strtolower($extensions[count($extensions) - 1]);
            if (in_array($doc_ext, $allowed_extensions) && !is_dir($doc_path)) {
                $doc_mime = mime_content_type($doc_path);
                echo "Indexing doc ".$row['id']." (".$row['path'].") in course ".$course['code']."\n";
                $residx = DocumentManager::index_document($row['id'], $course['code'], $row['session_id'], $course['course_language'], $specific_fields_values);
                if ($residx) {
                    echo "Success\n";
                } else {
                    echo "Failure\n";
                }
            }
        }
    }
}
