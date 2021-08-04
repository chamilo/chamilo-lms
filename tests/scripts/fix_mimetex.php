<?php

/* For licensing terms, see /license.txt */

exit;

require_once __DIR__ . '/../../main/inc/global.inc.php';

$sql = 'SELECT iid, id, question, c_id
        FROM c_quiz_question
        ORDER BY iid';

$result = Database::query($sql);
$data = Database::store_result($result);
$counter = 1;
$total = count($data);
echo 'Exercises to check: '.$total.PHP_EOL;

foreach ($data as $row) {
    $id = $row['id'];
    $courseId = $row['c_id'];

    $content = fixText($row['question']);
    $params = [
        'question' => $content
    ];
    Database::update('c_quiz_question', $params, ['id = ? and c_id = ?' => [$id, $courseId]]);

    $sql = "SELECT iid, answer, comment FROM c_quiz_answer WHERE question_id = $id and c_id = $courseId";
    $result = Database::query($sql);
    $items = Database::store_result($result, 'ASSOC');
    foreach ($items as $item) {
        $id = $item['iid'];
        $answer = fixText($item['answer']);
        $comment = fixText($item['comment']);
        $params = [
            'answer' => $answer,
            'comment' => $comment,
        ];
        Database::update('c_quiz_answer', $params, ['iid = ? ' => $id]);
    }
}

function fixText($content)
{
    $debug = false;
    if (strpos($content, 'mimetex.cgi') !== false) {
        $content = preg_replace('/[\r\n]+/', '', $content);
        //$debug = true;
        if ($debug) {
            var_dump($content);
        }
    }

    $matches = [];
    if (preg_match_all('/<img alt="(.*?)" (.*?)mimetex.cgi\? (.*?)\/>/', $content, $matches)) {
        $count = count($matches[0]);
        for ($i = 0; $i <= $count; $i++) {
            if (isset($matches[0]) && isset($matches[0][$i])) {
                $value = "<span class=\"math-tex\">`".$matches[1][$i]."`</span>";
                $content = str_replace($matches[0][$i], $value, $content);
            }
        }
    }

    $matches = [];
    /*<img (.*)mimetex.cgi\?(.*) title="(.*)?" alt="(.*)?" \/>
    <img (.*)?mimetex.cgi\? (.*)? title="(.*)?" alt="(.*)?" \/>*/
   // if       (preg_match_all('/<img (.*)?mimetex.cgi\? (.*)? title="(.*)?" alt="(.*)?" \/>/', $content, $matches)) {
    if (preg_match_all('/<img (.*?)mimetex.cgi\?(.*?) title="(.*?)" alt="(.*?)"/', $content, $matches)) {
        $count = count($matches[0]);
        for ($i = 0; $i <= $count; $i++) {
            if (isset($matches[0]) && isset($matches[0][$i])) {
                $value = "<span class=\"math-tex\">`".$matches[3][$i]."`</span>";
                $content = str_replace($matches[0][$i], $value, $content);
            }
        }
    }

    $matches = [];
    if (preg_match_all('/<img (.*?)mimetex.cgi\? (.*?) alt="(.*?)" title="(.*?)" \/>/', $content, $matches)) {
        $count = count($matches[0]);
        for ($i = 0; $i <= $count; $i++) {
            if (isset($matches[0]) && isset($matches[0][$i])) {
                $value = "<span class=\"math-tex\">`".$matches[3][$i]."`</span>";
                $content = str_replace($matches[0][$i], $value, $content);
            }
        }
    }

    $matches = [];
    if (preg_match_all('/<img (.*?)mimetex.cgi\?(.*?)title="(.*?)" alt="(.*?)"/', $content, $matches)) {
        $count = count($matches[0]);
        for ($i = 0; $i <= $count; $i++) {
            if (isset($matches[0]) && isset($matches[0][$i])) {
                $value = "<span class=\"math-tex\">`".$matches[3][$i]."`</span>";
                $content = str_replace($matches[0][$i], $value, $content);
            }
        }
    }

    $matches = [];
    if (preg_match_all('/<img (.*?)mimetex.cgi\? (.*?) title="(.*?)" alt="(.*?)"\/>/', $content, $matches)) {
        $count = count($matches[0]);
        for ($i = 0; $i <= $count; $i++) {
            if (isset($matches[0]) && isset($matches[0][$i])) {
                $value = "<span class=\"math-tex\">`".$matches[3][$i]."`</span>";
                $content = str_replace($matches[0][$i], $value, $content);
            }
        }
    }

    $matches = [];
    if (preg_match_all('/<img title="(.*?)" (.*?)mimetex.cgi\? (.*?) \/>/', $content, $matches)) {
        $count = count($matches[0]);
        for ($i = 0; $i <= $count; $i++) {
            if (isset($matches[0]) && isset($matches[0][$i])) {
                $value = "<span class=\"math-tex\">`".$matches[3][$i]."`</span>";
                $content = str_replace($matches[0][$i], $value, $content);
            }
        }
    }

    if ($debug) {
        var_dump($content);
    }

    return $content;
}
