<?php

/**
 * Class.
 */
class ImsAnswerHotspot extends Answer implements ImsAnswerInterface
{
    private $answerList = [];
    private $gradeList = [];

    /**
     * @todo update this to match hot spots instead of copying matching
     * Export the question part as a matrix-choice, with only one possible answer per line.
     *
     * @param string $questionIdent
     * @param string $questionStatment
     * @param string $questionDesc
     * @param string $questionMedia
     *
     * @return string
     */
    public function imsExportResponses($questionIdent, $questionStatment, $questionDesc = '', $questionMedia = '')
    {
        $this->answerList = $this->getAnswersList(true);
        $mediaFilePath = api_get_course_path().'/document/images/'.$questionMedia;
        $sysQuestionMediaPath = api_get_path(SYS_COURSE_PATH).$mediaFilePath;
        $questionMedia = api_get_path(WEB_COURSE_PATH).$mediaFilePath;
        $mimetype = mime_content_type($sysQuestionMediaPath);
        if (empty($mimetype)) {
            $mimetype = 'image/jpeg';
        }

        $text = '      <p>'.$questionStatment.'</p>'."\n";
        $text .= '      <graphicOrderInteraction responseIdentifier="hotspot_'.$questionIdent.'">'."\n";
        $text .= '        <prompt>'.$questionDesc.'</prompt>'."\n";
        $text .= '        <object type="'.$mimetype.'" width="250" height="230" data="'.$questionMedia.'">-</object>'."\n";
        if (is_array($this->answerList)) {
            foreach ($this->answerList as $key => $answer) {
                $key = $answer['id'];
                $answerTxt = $answer['answer'];
                $len = api_strlen($answerTxt);
                //coords are transformed according to QTIv2 rules here: http://www.imsproject.org/question/qtiv2p1pd/imsqti_infov2p1pd.html#element10663
                $coords = '';
                $type = 'default';
                switch ($answer['hotspot_type']) {
                    case 'square':
                        $type = 'rect';
                        $res = [];
                        $coords = preg_match('/^\s*(\d+);(\d+)\|(\d+)\|(\d+)\s*$/', $answer['hotspot_coord'], $res);
                        $coords = $res[1].','.$res[2].','.((int) $res[1] + (int) $res[3]).','.((int) $res[2] + (int) $res[4]);

                        break;
                    case 'circle':
                        $type = 'circle';
                        $res = [];
                        $coords = preg_match('/^\s*(\d+);(\d+)\|(\d+)\|(\d+)\s*$/', $answer['hotspot_coord'], $res);
                        $coords = $res[1].','.$res[2].','.sqrt(pow($res[1] - $res[3], 2) + pow($res[2] - $res[4]));

                        break;
                    case 'poly':
                        $type = 'poly';
                        $coords = str_replace([';', '|'], [',', ','], $answer['hotspot_coord']);

                        break;
                    case 'delineation':
                        $type = 'delineation';
                        $coords = str_replace([';', '|'], [',', ','], $answer['hotspot_coord']);

                        break;
                }
                $text .= '        <hotspotChoice shape="'.$type.'" coords="'.$coords.'" identifier="'.$key.'"/>'."\n";
            }
        }
        $text .= '      </graphicOrderInteraction>'."\n";

        return $text;
    }

    public function imsExportResponsesDeclaration($questionIdent, Question $question = null)
    {
        $this->answerList = $this->getAnswersList(true);
        $this->gradeList = $this->getGradesList();
        $out = '  <responseDeclaration identifier="hotspot_'.$questionIdent.'" cardinality="ordered" baseType="identifier">'."\n";
        if (is_array($this->answerList)) {
            $out .= '    <correctResponse>'."\n";
            foreach ($this->answerList as $answerKey => $answer) {
                $answerKey = $answer['id'];
                $answer = $answer['answer'];
                $out .= '<value><![CDATA['.formatExerciseQtiText($answerKey).']]></value>';
            }
            $out .= '    </correctResponse>'."\n";
        }
        $out .= '  </responseDeclaration>'."\n";

        return $out;
    }
}
