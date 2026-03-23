<?php

/* For license terms, see /license.txt */

require_once '../config.php';

api_protect_course_script(true);

$plugin = Test2pdfPlugin::create();
$letters = test2pdf_get_letters();

if (!test2pdf_is_plugin_active()) {
    Display::addFlash(
        Display::return_message(
            $plugin->get_lang('PluginDisabledFromAdminPanel'),
            'warning'
        )
    );

    header('Location: '.test2pdf_get_course_home_url());
    exit;
}

$courseId = (int) api_get_course_int_id();
$sessionId = (int) api_get_session_id();
$quizId = (int) ($_GET['id_quiz'] ?? 0);
$type = $_GET['type'] ?? 'all';

if (!in_array($type, ['question', 'answer', 'all'], true)) {
    $type = 'all';
}

if ($courseId <= 0 || $quizId <= 0) {
    Display::addFlash(Display::return_message(get_lang('Not found'), 'warning'));
    header('Location: view-pdf.php?'.api_get_cidreq());
    exit;
}

$infoCourse = api_get_course_info_by_id($courseId);
$infoQuiz = getInfoQuiz($courseId, $quizId, $sessionId);

if (empty($infoCourse) || empty($infoQuiz)) {
    Display::addFlash(Display::return_message(get_lang('Not found'), 'warning'));
    header('Location: view-pdf.php?'.api_get_cidreq());
    exit;
}

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
$pdf->MultiCell(0, 7, $titleQuiz, 0, 'L', false);

if (!empty($infoQuiz['description'])) {
    $pdf->WriteHTML(
        PDF::fixImagesPaths(
            removeQuotes($infoQuiz['description']),
            $infoCourse
        )
    );
}

$questionsList = getQuestionsFromCourse($courseId, $quizId, $sessionId);

if ('question' === $type || 'all' === $type) {
    $j = 1;
    foreach ($questionsList as $key => $value) {
        $infoQuestion = getInfoQuestion($courseId, $value);

        if (empty($infoQuestion)) {
            continue;
        }

        if ($pdf->y > 240) {
            $pdf->AddPage();
        }
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(64);
        $pdf->MultiCell(0, 7, ($key + $j).' - '.$infoQuestion['question'], 0, 'L', false);
        if (!empty($infoQuestion['description'])) {
            $pdf->WriteHTML(
                PDF::fixImagesPaths(
                    removeQuotes($infoQuestion['description']),
                    $infoCourse
                )
            );
        }

        $infoAnswer = getAnswers($courseId, $value);
        foreach ($infoAnswer as $key2 => $value2) {
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->SetTextColor(96);

            if (3 == $infoQuestion['type']) {
                $listAnswerInfo = getAnswerFillInBlanks($value2['answer']);
                $answerText = $listAnswerInfo['text'];

                if (!empty($listAnswerInfo['words_with_bracket'])) {
                    foreach ($listAnswerInfo['words_with_bracket'] as $wordWithBracket) {
                        if (!empty($wordWithBracket)) {
                            $answerText = str_replace($wordWithBracket, '[_____]', $answerText);
                        }
                    }
                }

                $answerText = str_replace(['<p>', '</p>'], ['', '. '], $answerText);
                $pdf->MultiCell(0, 5, removeHtml($answerText), 0, 'L', false);
            } else {
                $pdf->Cell(1, 7, '', 0, 0);
                $pdf->Rect($pdf->x + 2, $pdf->y, 4, 4);
                $pdf->Cell(7, 7, '', 0, 0);
                $pdf->MultiCell(0, 5, $letters[$key2].' - '.removeHtml($value2['answer']), 0, 'L', false);
            }

            $pdf->Ln(1);
        }
        $pdf->Ln(4);
    }
}
$j = 1;

if ('answer' === $type || 'all' === $type) {
    $answerList = [];
    foreach ($questionsList as $key => $value) {
        $infoQuestion = getInfoQuestion($courseId, $value);

        if (empty($infoQuestion)) {
            continue;
        }

        if ($infoQuestion['question'] == $plugin->get_lang('Statement')) {
            $j = 0;
        } else {
            $answers = '';

            if (
                2 == $infoQuestion['type'] ||
                3 == $infoQuestion['type'] ||
                9 == $infoQuestion['type'] ||
                11 == $infoQuestion['type'] ||
                12 == $infoQuestion['type'] ||
                14 == $infoQuestion['type']
            ) {
                $infoAnswer = getAnswers($courseId, $value);
                $answers .= ' '.($key + $j).' -';

                if (3 == $infoQuestion['type']) {
                    foreach ($infoAnswer as $value2) {
                        $listAnswerInfo = getAnswerFillInBlanks($value2['answer']);
                        if (!empty($listAnswerInfo['words'])) {
                            $answersList = implode(', ', $listAnswerInfo['words']);
                            $answers .= clearStudentAnswer($answersList);
                        }
                    }
                } else {
                    foreach ($infoAnswer as $key2 => $value2) {
                        if (1 == $value2['correct']) {
                            $answers .= ' '.$letters[$key2].',';
                        }
                    }

                    $i = strrpos($answers, ',');
                    if (false !== $i) {
                        $answers = substr($answers, 0, $i);
                    }
                    $answers .= ' ';
                }

                $answerList[] = $answers;
            } else {
                $infoAnswer = getAnswers($courseId, $value);
                foreach ($infoAnswer as $key2 => $value2) {
                    if (1 == $value2['correct']) {
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
        if (0 == $i % 4) {
            $pdf->Ln();
        }
        $i++;
    }
}

$typeSuffix = 'q';

switch ($type) {
    case 'question':
        $typeSuffix = 'Q';
        break;
    case 'answer':
        $typeSuffix = 'A';
        break;
    case 'all':
        $typeSuffix = 'QA';
        break;
}

$filename = 'C'.$courseId.'-S'.($sessionId ?: 0).'-Q'.$quizId.'-'.$typeSuffix.'.pdf';
$pdf->Output($filename, 'I');
