<?php
/* For license terms, see /license.txt */

require_once '../config.php';

$plugin = Test2pdfPlugin::create();
$enable = $plugin->get('enable_plugin') == 'true';
if (!$enable) {
    header('Location: ../../../index.php');
    exit;
}

api_protect_course_script();

$courseId = intval($_GET['c_id']);
$sessionId = api_get_session_id();
$quizId = intval($_GET['id_quiz']);

$infoCourse = api_get_course_info_by_id($courseId);
$infoQuiz = getInfoQuiz($courseId, $quizId);
$titleCourse = removeHtml($infoCourse['title']);
$titleQuiz = removeHtml($infoQuiz['title']);

$mpdf = new PDF();
$mpdf->set_header($infoCourse);
$mpdf->set_footer();
$pdf = $mpdf->pdf;
$pdf->SetTitle($titleCourse.' - '.$titleQuiz);
$pdf->AddPage();

$pdf->SetFont('Arial', '', 16);
$pdf->SetTextColor(64);
$pdf->MultiCell(0, 7, $infoQuiz['title'], 0, 'L', false);
if (!empty($infoQuiz['description'])) {
    $pdf->WriteHTML(removeQuotes($infoQuiz['description']));
}

// Select all questions of the supported types from the given course
$questionsList = getQuestionsFromCourse($courseId, $quizId, $sessionId);

// Go through all questions and get the answers
if ($_GET['type'] == 'question' || $_GET['type'] == 'all') {
    $j = 1;
    foreach ($questionsList as $key => $value) {
        $infoQuestion = getInfoQuestion($courseId, $value);
        if ($pdf->y > 240) {
            $pdf->AddPage();
        }
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(64);
        $pdf->MultiCell(0, 7, ($key + $j).' - '.$infoQuestion['question'], 0, 'L', false);
        if (!empty($infoQuestion['description'])) {
            $pdf->WriteHTML(removeQuotes($infoQuestion['description']));
        }

        $infoAnswer = getAnswers($courseId, $value);
        foreach ($infoAnswer as $key2 => $value2) {
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->SetTextColor(96);
            $pdf->Cell(1, 7, '', 0, 0);
            $pdf->Rect($pdf->x + 2, $pdf->y, 4, 4);
            $pdf->Cell(7, 7, '', 0, 0);
            $pdf->MultiCell(0, 5, $letters[$key2].' - '.removeHtml($value2['answer']), 0, 'L', false);
            $pdf->Ln(1);
        }
        $pdf->Ln(4);
    }
}
$j = 1;
if ($_GET['type'] == 'answer' || $_GET['type'] == 'all') {
    $answerList = [];
    foreach ($questionsList as $key => $value) {
        $infoQuestion = getInfoQuestion($courseId, $value);
        if ($infoQuestion['question'] == $plugin->get_lang('Statement')) {
            $j = 0;
        } else {
            $answers = '';
            $infoQuestion = getInfoQuestion($courseId, $value);
            if ($infoQuestion['type'] == 2 ||
                $infoQuestion['type'] == 9 ||
                $infoQuestion['type'] == 11 ||
                $infoQuestion['type'] == 12 ||
                $infoQuestion['type'] == 14
            ) {
                $infoAnswer = getAnswers($courseId, $value);
                $answers .= ' '.($key + $j).' -';
                foreach ($infoAnswer as $key2 => $value2) {
                    if ($value2['correct'] == 1) {
                        $answers .= ' '.$letters[$key2].',';
                    }
                }
                $i = strrpos($answers, ',');
                $answers = substr($answers, 0, $i);
                $answers .= ' ';
                $answerList[] = $answers;
            } else {
                $infoAnswer = getAnswers($courseId, $value);
                foreach ($infoAnswer as $key2 => $value2) {
                    if ($value2['correct'] == 1) {
                        $answers .= ' '.($key + $j).' - '.$letters[$key2].' ';
                        break;
                    }
                }
                $answerList[] = $answers;
            }
        }
    }
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(64);
    $pdf->Cell(0, 7, $plugin->get_lang('AnswersColumn'), 0, 1, 'L', false);

    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(64, 64, 255);
    $i = 1;
    foreach ($answerList as $resp) {
        $pdf->Cell(50, 6, $resp, 0);
        if ($i % 4 == 0) {
            $pdf->Ln();
        }
        $i++;
    }
}

$pdf->Output();
