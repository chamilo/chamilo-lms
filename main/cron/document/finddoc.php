<?php
/**
 * Script to find a document with a specific title or path in all courses
 */
/**
 * Code init - comment die() call to enable
 */
die();
require '../../inc/global.inc.php';
require_once '../../inc/lib/course.lib.php';
if (empty($_GET['doc'])) {
  echo "To add a document name to search, add ?doc=abc to the URL";
} else {
  echo "Received param ".Security::remove_XSS($_GET['doc'])."<br />";
}
$courses_list =  CourseManager::get_courses_list();
foreach ($courses_list as $course) {
  $title = Database::escape_string($_GET['doc']);
  $td = Database::get_course_table(TABLE_DOCUMENT);
  $sql = "SELECT id, path FROM $td WHERE c_id = ".$course['id']." AND path LIKE '%$title%' OR title LIKE '%$title%'";
  $res = Database::query($sql);
  if (Database::num_rows($res)>0) {
    while ($row = Database::fetch_array($res)) {
      echo "Found doc ".$row['id']."-> ".$row['path']." in course ".$course['code']."<br />";
    }
  }
}
