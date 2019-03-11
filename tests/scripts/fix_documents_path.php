<?php
/* For licensing terms, see /license.txt */

exit;

require_once __DIR__.'/../../main/inc/global.inc.php';

$courses = CourseManager::get_courses_list();

// Old values
$webPath = 'xx/'; // With out protocol (http://) Example: 'myportal.com'
$customCourseFolder = 'yy'; // Custom course folder

// New values
$newUrlAppend = '/zz'; // Url append of new portal

// Path to search
$pathToSearch = $webPath.'../../../../../../../'.$customCourseFolder;

// Will find http or https
$pathToSearch = ['http://'.$pathToSearch, 'https://'.$pathToSearch];

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
            $newContent = str_replace($pathToSearch, $newUrlAppend.'/courses', $contents);
            file_put_contents($file->getRealPath(), $newContent);
        }

        // Updating exercises
        $sql = "SELECT iid, description FROM c_quiz WHERE c_id = $courseId";
        $result = Database::query($sql);
        $items = Database::store_result($result);
        foreach ($items as $item) {
            $id = $item['iid'];
            $newContent = str_replace($pathToSearch, $newUrlAppend.'/courses', $item['description']);
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
            $description = str_replace($pathToSearch, $newUrlAppend.'/courses', $item['description']);
            $description = Database::escape_string($description);

            $question = str_replace($pathToSearch, $newUrlAppend.'/courses', $item['question']);
            $question = Database::escape_string($question);

            $sql = "UPDATE c_quiz_question SET 
                      description = '$newContent',
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
            $answer = str_replace($pathToSearch, $newUrlAppend.'/courses', $item['answer']);
            $answer = Database::escape_string($answer);

            $comment = str_replace($pathToSearch, $newUrlAppend.'/courses', $item['comment']);
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

            $text = str_replace($pathToSearch, $newUrlAppend.'/courses', $item['intro_text']);
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
