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
//$pathToSearch = '#http(.*)://(.*)'.$slashes.'([^/]+)/(.*)/#';
$pathToSearch = '#(http:\/\/\s*|https:\/\/\s*)?(www\s*)?(?(1)([.]\s*))?(?(2)([.]\s*))?([a-zA-Z0-9.-]{2,256})(\s*[.]\s*)(com|fr|)('.$slashes.')(.*?)/(.*?)/(.*?)/#';
$pathToReplace = $newUrlAppend.'/courses/${10}/${11}/';

// tests
/*
$contents = 'http://something.fr/../../../../../../../courses-lt-marie-chamilo/ABC/document/images/gallery/science.jpg';
$contents .= '<br />http://something.something.fr/../../../../../../../courses-lt-marie-chamilo/CDE/document/science.jpg';
$newContent = preg_replace($pathToSearch, $pathToReplace, $contents);
echo $newContent;
exit;*/

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
            $newContent = preg_replace($pathToSearch, $pathToReplace, $contents);
            file_put_contents($file->getRealPath(), $newContent);
        }

        // Updating exercises
        $sql = "SELECT iid, description FROM c_quiz WHERE c_id = $courseId";
        $result = Database::query($sql);
        $items = Database::store_result($result);
        foreach ($items as $item) {
            $id = $item['iid'];
            $newContent = preg_replace($pathToSearch, $pathToReplace, $item['description']);
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
            $description = preg_replace($pathToSearch, $pathToReplace, $item['description']);
            $description = Database::escape_string($description);

            $question = preg_replace($pathToSearch, $pathToReplace, $item['question']);
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
            $answer = preg_replace($pathToSearch, $pathToReplace, $item['answer']);
            $answer = Database::escape_string($answer);

            $comment = preg_replace($pathToSearch, $pathToReplace, $item['comment']);
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
            $text = preg_replace($pathToSearch, $pathToReplace, $item['intro_text']);
            $text = Database::escape_string($text);

            $sql = "UPDATE c_tool_intro SET
                      intro_text = '$text'
                    WHERE iid = $id";
            Database::query($sql);
        }

        // Updating Glossary
        $sql = "SELECT iid, description FROM c_glossary WHERE c_id = $courseId";
        $result = Database::query($sql);
        $items = Database::store_result($result);
        foreach ($items as $item) {
            $id = $item['iid'];
            $gdescription = preg_replace($pathToSearch, $pathToReplace, $item['description']);
            $gdescription = Database::escape_string($gdescription);
            $sql = "UPDATE c_glossary SET description = '$gdescription' WHERE iid = $id";
            Database::query($sql);
        }

        // Updating forums
        $sql = "SELECT iid, forum_comment FROM c_forum_forum WHERE c_id = $courseId";
        $result = Database::query($sql);
        $items = Database::store_result($result);
        foreach ($items as $item) {
            $id = $item['iid'];
            $text = preg_replace($pathToSearch, $pathToReplace, $item['forum_comment']);
            $text = Database::escape_string($text);

            $sql = "UPDATE c_forum_forum SET
                      forum_comment = '$text'
                    WHERE iid = $id";
            Database::query($sql);
        }

        // Updating posts
        $sql = "SELECT iid, post_text FROM c_forum_post WHERE c_id = $courseId";
        $result = Database::query($sql);
        $items = Database::store_result($result);
        foreach ($items as $item) {
            $id = $item['iid'];
            $text = preg_replace($pathToSearch, $pathToReplace, $item['post_text']);
            $text = Database::escape_string($text);

            $sql = "UPDATE c_forum_post SET
                      post_text = '$text'
                    WHERE iid = $id";
            Database::query($sql);
        }

        // Updating forum cats
        $sql = "SELECT iid, cat_comment FROM c_forum_category WHERE c_id = $courseId";
        $result = Database::query($sql);
        $items = Database::store_result($result);
        foreach ($items as $item) {
            $id = $item['iid'];
            $text = preg_replace($pathToSearch, $pathToReplace, $item['cat_comment']);
            $text = Database::escape_string($text);

            $sql = "UPDATE c_forum_category SET
                      cat_comment = '$text'
                    WHERE iid = $id";
            Database::query($sql);
        }
    } else {
        echo "<h4>Path doesn't exist</h4>".'<br />';
    }
}

