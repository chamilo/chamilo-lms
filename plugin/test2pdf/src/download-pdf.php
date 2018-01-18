<?php
/* For license terms, see /license.txt */
/**
 * Configuration script for the Ranking plugin
 * @package chamilo.plugin.ranking
 */

require_once '../../../main/inc/global.inc.php';

$test2pdfPlugin = Test2pdfPlugin::create();
$enable = $test2pdfPlugin->get('enable_plugin');
if ($enable != "true") {
    header('Location: ../../../index.php');
}

api_protect_course_script();

$course_id = (int)$_GET['c_id'];
$id_quiz = (int)$_GET['id_quiz'];

class TestToPDF extends FPDF
{
    public function Header()
    {
        global $title_course;
        global $title_quiz;

        $logo = '../resources/img/logo.png';
        $this->Image($logo, 10, 0);

        // Title
        $this->SetFont('Courier', 'I', 14);
        $this->Cell(0, 5, $title_course, 0, 1, 'R');
        $this->SetFont('Helvetica', 'I', 14);
        $this->Cell(0, 5, $title_quiz, 0, 1, 'R');

        // Line break
        $this->SetLineWidth(0.5);
        $this->SetDrawColor(60, 120, 255);
        $this->Line(10, 24, 200, 24);
        $this->SetLineWidth(0.4);
        $this->SetDrawColor(200);
        $this->Line(11, 24.5, 199, 24.5);
        $this->Ln(10);
    }

    public function Footer()
    {
        global $test2pdfPlugin;
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        $y = $this->GetY();
        // Line break
        $this->SetLineWidth(0.5);
        $this->SetDrawColor(60, 120, 255);
        $this->Line(10, $y, 200, $y);
        $this->SetLineWidth(0.4);
        $this->SetDrawColor(200);
        $this->Line(11, $y+0.5, 199, $y+0.5);


        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Text color in gray
        $this->SetTextColor(128);
        // Page number
        $this->Cell(0, 10, utf8_decode(sprintf($test2pdfPlugin->get_lang('PageX'), $this->PageNo())), 0, 0, 'C');
        $this->Cell(0, 10, date('Y'), 0, 0, 'R');
    }

    public $B;
    public $I;
    public $U;
    public $HREF;
    public $fontList;
    public $issetfont;
    public $issetcolor;

    public function PDF($orientation='P', $unit='mm', $format='A4')
    {
        //Call parent constructor
        $this->__construct($orientation, $unit, $format);
        //Initialization
        $this->B=0;
        $this->I=0;
        $this->U=0;
        $this->HREF='';
        $this->fontlist=['arial', 'times', 'courier', 'helvetica', 'symbol'];
        $this->issetfont=false;
        $this->issetcolor=false;
    }

    public function WriteHTML($html)
    {
        //HTML parser
        $html=strip_tags($html, "<b><u><i><a><img><p><br><strong><em><font><tr><blockquote><style>"); //supprime tous les tags sauf ceux reconnus
        $html=str_replace("\n", ' ', $html); //remplace retour à la ligne par un espace
        $a=preg_split('/<(.*)>/U', $html, -1, PREG_SPLIT_DELIM_CAPTURE); //éclate la chaîne avec les balises
        foreach ($a as $i=>$e) {
            if ($i%2==0) {
                //Text
                if ($this->HREF) {
                    $this->PutLink($this->HREF, $e);
                } else {
                    $this->Write(5, stripslashes(txtentities($e)));
                }
            } else {
                //Tag
                if ($e[0]=='/') {
                    $this->CloseTag(strtoupper(substr($e, 1)));
                } else {
                    //Extract attributes
                    $a2=explode(' ', $e);
                    $tag=strtoupper(array_shift($a2));
                    $attr=[];
                    foreach ($a2 as $v) {
                        if (preg_match('/([^=]*)=["\']?([^"\']*)/', $v, $a3)) {
                            $attr[strtoupper($a3[1])]=$a3[2];
                        }
                    }
                    $this->OpenTag($tag, $attr);
                }
            }
        }
    }

    public function OpenTag($tag, $attr)
    {
        //Opening tag
        switch ($tag) {
            case 'STRONG':
                $this->SetStyle('B', true);
                break;
            case 'EM':
                $this->SetStyle('I', true);
                break;
            case 'B':
            case 'I':
            case 'U':
                $this->SetStyle($tag, true);
                break;
            case 'A':
                $this->HREF=$attr['HREF'];
                break;
            case 'IMG':
                if (isset($attr['SRC']) && (isset($attr['WIDTH']) || isset($attr['HEIGHT']))) {
                    if (!isset($attr['WIDTH'])) {
                        $attr['WIDTH'] = 0;
                    }
                    if (!isset($attr['HEIGHT'])) {
                        $attr['HEIGHT'] = 0;
                    }
                    $this->Image($attr['SRC'], $this->GetX(), $this->GetY(), px2mm($attr['WIDTH']), px2mm($attr['HEIGHT']));
                }
                break;
            case 'TR':
            case 'BLOCKQUOTE':
            case 'BR':
                $this->Ln(5);
                break;
            case 'P':
                $this->Ln(10);
                break;
            case 'FONT':
                if (isset($attr['COLOR']) && $attr['COLOR']!='') {
                    $coul=hex2dec($attr['COLOR']);
                    $this->SetTextColor($coul['R'], $coul['V'], $coul['B']);
                    $this->issetcolor=true;
                }
                if (isset($attr['FACE']) && in_array(strtolower($attr['FACE']), $this->fontlist)) {
                    $this->SetFont(strtolower($attr['FACE']));
                    $this->issetfont=true;
                }
                break;
        }
    }

    public function CloseTag($tag)
    {
        //Closing tag
        if ($tag=='STRONG') {
            $tag='B';
        }
        if ($tag=='EM') {
            $tag='I';
        }
        if ($tag=='B' || $tag=='I' || $tag=='U') {
            $this->SetStyle($tag, false);
        }
        if ($tag=='A') {
            $this->HREF='';
        }
        if ($tag=='FONT') {
            if ($this->issetcolor==true) {
                $this->SetTextColor(0);
            }
            if ($this->issetfont) {
                $this->SetFont('arial');
                $this->issetfont=false;
            }
        }
    }

    public function SetStyle($tag, $enable)
    {
        //Modify style and select corresponding font
        $this->$tag+=($enable ? 1 : -1);
        $style='';
        foreach (['B','I','U'] as $s) {
            if ($this->$s>0) {
                $style.=$s;
            }
        }
        $this->SetFont('', $style);
    }

    public function PutLink($URL, $txt)
    {
        //Put a hyperlink
        $this->SetTextColor(0, 0, 255);
        $this->SetStyle('U', true);
        $this->Write(5, $txt, $URL);
        $this->SetStyle('U', false);
        $this->SetTextColor(0);
    }
}

//Obtener nombre del curso y nombre del ejercicio
//$info_course = CourseManager::get_course_information_by_id($course_id);
$info_course = api_get_course_info_by_id($course_id);
$info_quiz = getInfoQuiz($course_id, $id_quiz);
$title_course = utf8_decode(removeHtml($info_course['title']));
$title_quiz = utf8_decode(removeHtml($info_quiz['title']));

$pdf = new TestToPDF();
$pdf->SetTitle($title_course.' - '.$title_quiz);
$pdf->AddPage();

// Select all questions of the supported types from the given course
$array_question_id = getQuestions($course_id, $id_quiz);

// Go through all questions and get the answers
if ($_GET['type'] == 'question' || $_GET['type'] == 'all') {
    $j = 1;
    foreach ($array_question_id as $key => $value) {
        $InfoQuestion = getInfoQuestion($course_id, $value);
        if ($pdf->GetY() > 240) {
            $pdf->AddPage();
        }
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(64);

        if (trim($InfoQuestion['description'])!='') {
            $j = 0;
            $pdf->WriteHTML(utf8_decode(removeQuotes($InfoQuestion['description'])));
            $pdf->Ln();
        } else {
            $pdf->MultiCell(0, 7, ($key+$j).' - '.utf8_decode($InfoQuestion['question']), 0, 'L', false);
        }
        /*

        if($InfoQuestion['question'] == "Enunciado"){
            $j = 0;
            $pdf->MultiCell(0,7,utf8_decode($InfoQuestion['question']),0,'L',false);
            if(trim($InfoQuestion['description'])!=''){
                $pdf->WriteHTML(utf8_decode(quitar_acentos($InfoQuestion['description'])));
                $pdf->Ln();
            }
        }else{
            $pdf->MultiCell(0,7,($key+$j).' - '.utf8_decode($InfoQuestion['question']),0,'L',false);
        }*/
        $InfoAnswer = getAnswers($course_id, $value);
        foreach ($InfoAnswer as $key2 => $value2) {
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->SetTextColor(96);
            $pdf->Cell(1, 7, '', 0, 0);
            $pdf->Rect($pdf->GetX()+2, $pdf->GetY(), 4, 4);
            $pdf->Cell(7, 7, '', 0, 0);
            $pdf->MultiCell(0, 5, $letters[$key2].' - '.utf8_decode(removeHtml($value2['answer'])), 0, 'L', false);
            $pdf->Ln(1);
        }
        $pdf->Ln(4);
    }
}
$j=1;
if ($_GET['type'] == 'answer' || $_GET['type'] == 'all') {
    $array_resp = [];
    foreach ($array_question_id as $key => $value) {
        $InfoQuestion = getInfoQuestion($course_id, $value);
        if ($InfoQuestion['question'] == $test2pdfPlugin->get_lang('Statement')) {
            $j = 0;
        } else {
            $respuestas = '';
            $InfoQuestion = getInfoQuestion($course_id, $value);
            if ($InfoQuestion['type']==2 || $InfoQuestion['type']==9 || $InfoQuestion['type']==11 || $InfoQuestion['type']==12 || $InfoQuestion['type']==14) {
                $InfoAnswer = getAnswers($course_id, $value);
                $respuestas .= ' '.($key+$j).' -';
                foreach ($InfoAnswer as $key2 => $value2) {
                    if ($value2['correct'] == 1) {
                        $respuestas .= ' '.$letters[$key2].',';
                    }
                }
                $i = strrpos($respuestas, ',');
                $respuestas = substr($respuestas, 0, $i);
                $respuestas .= ' ';
                $array_resp[] = $respuestas;
            } else {
                $InfoAnswer = getAnswers($course_id, $value);
                foreach ($InfoAnswer as $key2 => $value2) {
                    if ($value2['correct'] == 1) {
                        $respuestas .= ' '.($key+$j).' - '.$letters[$key2].' ';
                        break;
                    }
                }
                $array_resp[] = $respuestas;
            }
        }
    }
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(64);
    $pdf->Cell(0, 7, utf8_decode($test2pdfPlugin->get_lang('AnswersColumn')), 0, 1, 'L', false);

    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(64, 64, 255);
    $i = 1;
    foreach ($array_resp as $resp) {
        $pdf->Cell(50, 6, $resp, 0);
        if ($i%4 == 0) {
            $pdf->Ln();
        }
        $i++;
    }
}

$pdf->Output();
