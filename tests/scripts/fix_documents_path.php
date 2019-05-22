<?php
/* For licensing terms, see /license.txt */

/**
 * This script will find paths with:
 *
 * https://example.fr/../../../../../../../LOLOS/CHAMILO/document/Chamilo/Fonctionalite_de_groupe.html
 *
 * And will be transformed to:
 *
 * /NEWFOLDER/courses/CHAMILO/document/Chamilo/Fonctionalite_de_groupe.html
 *
 * You need to choose the /NEWFOLDER in the $newUrlAppend variable
 *
 * The script will edit HTML documents inside every document course folder and also edit
 * exercises (question, answer) and course description in the database.
 *
 */

exit;

require_once __DIR__.'/../../main/inc/global.inc.php';

/*$test = '
https://example.fr/../../../../../../../LOLOS/CHAMILO/document/Chamilo/Fonctionalite_de_groupe.html
http://example.fr/../../../../../../../LOLOS/CHAMILO/document/Chamilo/Fonctionalite_de_groupe.html
';*/

$courses = CourseManager::get_courses_list();

// New values
$newUrlAppend = '/NEWFOLDER'; // Url append of new portal

$slashes = '/../../../../../../../';
$pathToSearch = '#http(.*)://(.*)'.$slashes.'([^/]+)/(.*)#';

$courseSysPath = api_get_path(SYS_COURSE_PATH);
foreach ($courses as $course) {
    $courseId = $course['real_id'];
    $docsPath = $courseSysPath.$course['directory'].'/document/';

    echo "<h4>Course in: $docsPath</h4>".'<br />';
    if (is_dir($docsPath)) {
        $finder = new \Symfony\Component\Finder\Finder();
        $finder->files()->in($docsPath)->name('*.html');

        foreach ($finder as $file) {
            echo $file->getRealPath().'<br />';
            $contents = file_get_contents($file->getRealPath());
            $newContent = preg_replace($pathToSearch, $newUrlAppend.'/courses/${4}', $contents);
            file_put_contents($file->getRealPath(), $newContent);
        }

        // Updating exercises
        $sql = "SELECT iid, description FROM c_quiz WHERE c_id = $courseId";
        $result = Database::query($sql);
        $items = Database::store_result($result);
        foreach ($items as $item) {
            $id = $item['iid'];
            $newContent = preg_replace($pathToSearch, $newUrlAppend.'/courses/${4}', $item['description']);
            $newContent = Database::escape_string($newContent);
            $sql = "UPDATE c_quiz SET description = '$newContent' WHERE iid = $id";
            Database::query($sql);
        }

        // Updating questions
        $sql = "SELECT iid, question, description FROM c_quiz_question WHERE c_id = $courseId";
        $result = Database::query($sql);
        $items = Database::store_result($result);
        foreach ($items as $item) {
            $id = $item['iid'];
            $description = preg_replace($pathToSearch, $newUrlAppend.'/courses/${4}', $item['description']);
            $description = Database::escape_string($description);

            $question = preg_replace($pathToSearch, $newUrlAppend.'/courses/${4}', $item['question']);
            $question = Database::escape_string($question);

            $sql = "UPDATE c_quiz_question SET 
                      description = '$description',
                      question = '$question'
                    WHERE iid = $id";
            Database::query($sql);
        }

        // Updating answer
        $sql = "SELECT iid, answer, comment FROM c_quiz_answer WHERE c_id = $courseId";
        $result = Database::query($sql);
        $items = Database::store_result($result);
        foreach ($items as $item) {
            $id = $item['iid'];
            $answer = preg_replace($pathToSearch, $newUrlAppend.'/courses/${4}', $item['answer']);
            $answer = Database::escape_string($answer);

            $comment = preg_replace($pathToSearch, $newUrlAppend.'/courses/${4}', $item['comment']);
            $comment = Database::escape_string($comment);

            $sql = "UPDATE c_quiz_answer SET 
                      answer = '$answer',
                      comment = '$comment'
                    WHERE iid = $id";
            Database::query($sql);
        }

        // Updating intros
        $sql = "SELECT iid, intro_text FROM c_tool_intro WHERE c_id = $courseId";
        $result = Database::query($sql);
        $items = Database::store_result($result);
        foreach ($items as $item) {
            $id = $item['iid'];
            $text = preg_replace($pathToSearch, $newUrlAppend.'/courses/${4}', $item['intro_text']);
            $text = Database::escape_string($text);

            $sql = "UPDATE c_tool_intro SET
                      intro_text = '$text'
                    WHERE iid = $id";
            Database::query($sql);
        }
    } else {
        echo "<h4>Path doesn't exist</h4>".'<br />';
    }
}
