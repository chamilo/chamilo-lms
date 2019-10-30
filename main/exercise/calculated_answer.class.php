<?php
/* For licensing terms, see /license.txt */

use Webit\Util\EvalMath\EvalMath;

/**
 *  Class CalculatedAnswer
 *  This class contains calculated answer form and answer processing functions.
 *
 *  @author Imanol Losada
 */
class CalculatedAnswer extends Question
{
    public $typePicture = 'calculated_answer.png';
    public $explanationLangVar = 'CalculatedAnswer';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = CALCULATED_ANSWER;
        $this->isContent = $this->getIsContent();
    }

    /**
     * {@inheritdoc}
     */
    public function createAnswersForm($form)
    {
        $defaults = [];
        //$defaults['answer'] = get_lang('<table cellspacing="0" cellpadding="10" border="1" width="720" style="" height:="">    <tbody>        <tr>            <td colspan="2">            <h3>Example fill the form activity : calculate the Body Mass Index</h3>            </td>        </tr>        <tr>            <td style="text-align: right;"><strong>Age</strong></td>            <td width="75%" style="">[25] years old</td>        </tr>        <tr>            <td style="text-align: right;"><strong>Sex</strong></td>            <td style="" text-align:="">[M] (M or F)</td>        </tr>        <tr>            <td style="text-align: right;"><strong>Weight</strong></td>            <td style="" text-align:="">95 Kg</td>        </tr>        <tr>            <td style="vertical-align: top; text-align: right;"><strong>Height</strong></td>            <td style="vertical-align: top;">1.81 m</td>        </tr>        <tr>            <td style="vertical-align: top; text-align: right;"><strong>Body Mass Index</strong></td>            <td style="vertical-align: top;">[29] BMI =Weight/Size<sup>2</sup> (Cf.<a href="http://en.wikipedia.org/wiki/Body_mass_index" onclick="window.open(this.href,'','resizable=yes,location=yes,menubar=no,scrollbars=yes,status=yes,toolbar=no,fullscreen=no,dependent=no,width=800,height=600,left=40,top=40,status'); return false"> Wikipedia article</a>)</td>        </tr>    </tbody></table>');
        $defaults['answer'] = get_lang('DefaultTextInBlanks');
        if (!empty($this->id)) {
            $objAnswer = new Answer($this->id);
            $preArray = explode('@@', $objAnswer->selectAnswer(1));
            $defaults['formula'] = array_pop($preArray);
            $defaults['answer'] = array_shift($preArray);
            $defaults['answer'] = preg_replace("/\[.*\]/", '', $defaults['answer']);
            $defaults['weighting'] = $this->weighting;
        }
        $lowestValue = '1.00';
        $highestValue = '20.00';

        // javascript //
        echo '<script>
            function parseTextNumber(textNumber, floatValue) {
                if (textNumber.indexOf(".") > -1) {
                    textNumber = parseFloat(textNumber);
                    floatValue.exists = "true";
                } else {
                    textNumber = parseInt(textNumber);
                }
                return textNumber;
            }

            function updateRandomValue(element) {
                // "floatValue" helps to distinguish between an integer (10) and a float with all 0 decimals (10.00)
                var floatValue = { exists: "false" };
                var index = (element.name).match(/\[[^\]]*\]/g);
                var lowestValue = parseTextNumber(document.getElementById("lowestValue"+index).value, floatValue);
                var highestValue = parseTextNumber(document.getElementById("highestValue"+index).value, floatValue);
                var result = Math.random() * (highestValue - lowestValue) + lowestValue;
                if (floatValue.exists == "true") {
                    result = parseFloat(result).toFixed(2);
                } else {
                    result = parseInt(result);
                }
                document.getElementById("randomValue"+index).innerHTML = "'.get_lang("Range value").': " + result;
           }

            CKEDITOR.on("instanceCreated", function(e) {
                if (e.editor.name === "answer") {
                    e.editor.on("change", updateBlanks);
                }
            });

            var firstTime = true;
            function updateBlanks(e) {
                if (firstTime) {
                    field = document.getElementById("answer");
                    var answer = field.value;
                } else {
                    var answer = e.editor.getData();
                }
                var blanks = answer.match(/\[[^\]]*\]/g);
                var fields = "<div class=\"form-group\"><label class=\"col-sm-2\">'.get_lang('Variable ranges').'</label><div class=\"col-sm-8\"><table>";
                if (blanks!=null) {
                    if (typeof updateBlanks.randomValues === "undefined") {
                        updateBlanks.randomValues = [];
                    }
                    for (i=0 ; i<blanks.length ; i++){
                        if (document.getElementById("lowestValue["+i+"]") && document.getElementById("highestValue["+i+"]")) {
                            lowestValue = document.getElementById("lowestValue["+i+"]").value;
                            highestValue = document.getElementById("highestValue["+i+"]").value;
                        } else {
                            lowestValue = '.$lowestValue.'.toFixed(2);
                            highestValue = '.$highestValue.'.toFixed(2);
                            for (j=0; j<blanks.length; j++) {
                                updateBlanks.randomValues[j] = parseFloat(Math.random() * (highestValue - lowestValue) + lowestValue).toFixed(2);
                            }
                        }
                        fields += "<tr><td><label>"+blanks[i]+"</label></td><td><input class=\"span1\" style=\"margin-left: 0em;\" size=\"5\" value=\""+lowestValue+"\" type=\"text\" id=\"lowestValue["+i+"]\" name=\"lowestValue["+i+"]\" onblur=\"updateRandomValue(this)\"/></td><td><input class=\"span1\" style=\"margin-left: 0em; width:80px;\" size=\"5\" value=\""+highestValue+"\" type=\"text\" id=\"highestValue["+i+"]\" name=\"highestValue["+i+"]\" onblur=\"updateRandomValue(this)\"/></td><td><label class=\"span3\" id=\"randomValue["+i+"]\"/>'.get_lang('Range value').': "+updateBlanks.randomValues[i]+"</label></td></tr>";
                    }
                }
                document.getElementById("blanks_weighting").innerHTML = fields + "</table></div></div>";
                if (firstTime) {
                    firstTime = false;
                }
            }

            window.onload = updateBlanks;

        </script>';

        // answer
        $form->addElement('label', null, '<br /><br />'.get_lang('Please type your text below').', '.get_lang('and').' '.get_lang('use square brackets [...] to define one or more blanks'));
        $form->addElement(
            'html_editor',
            'answer',
            Display::return_icon('fill_field.png'),
            [
                'id' => 'answer',
                'onkeyup' => 'javascript: updateBlanks(this);',
            ],
            [
                'ToolbarSet' => 'TestQuestionDescription',
                'Width' => '100%',
                'Height' => '350',
            ]
        );

        $form->addRule('answer', get_lang('Please type the text'), 'required');
        $form->addRule('answer', get_lang('Please define at least one blank with square brackets [...]'), 'regex', '/\[.*\]/');

        $form->addElement('label', null, get_lang('If you want only integer values write both limits without decimals'));
        $form->addElement('html', '<div id="blanks_weighting"></div>');

        $notationListButton = Display::url(
            get_lang('Formula notation'),
            api_get_path(WEB_CODE_PATH).'exercise/evalmathnotation.php',
            [
                'class' => 'btn btn-info ajax',
                'data-title' => get_lang('Formula notation'),
                '_target' => '_blank',
            ]
        );
        $form->addElement(
            'label',
            null,
            $notationListButton
        );

        $form->addElement('text', 'formula', [get_lang('Formula'), get_lang('Formula sample: sqrt( [x] / [y] ) * ( e ^ ( ln(pi) ) )')], ['id' => 'formula']);
        $form->addRule('formula', get_lang('Please, write the formula'), 'required');

        $form->addElement('text', 'weighting', get_lang('Score'), ['id' => 'weighting']);
        $form->setDefaults(['weighting' => '10']);

        $form->addElement('text', 'answerVariations', get_lang('Question variations'));
        $form->addRule(
            'answerVariations',
            get_lang('GiveQuestion variations'),
            'required'
        );
        $form->setDefaults(['answerVariations' => '1']);

        global $text;
        // setting the save button here and not in the question class.php
        $form->addButtonSave($text, 'submitQuestion');

        if (!empty($this->id)) {
            $form->setDefaults($defaults);
        } else {
            if ($this->isContent == 1) {
                $form->setDefaults($defaults);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processAnswersCreation($form, $exercise)
    {
        if (!self::isAnswered()) {
            $table = Database::get_course_table(TABLE_QUIZ_ANSWER);
            Database::delete(
                $table,
                [
                    'c_id = ? AND question_id = ?' => [
                        $this->course['real_id'],
                        $this->id,
                    ],
                ]
            );
            $answer = $form->getSubmitValue('answer');
            $formula = $form->getSubmitValue('formula');
            $lowestValues = $form->getSubmitValue('lowestValue');
            $highestValues = $form->getSubmitValue('highestValue');
            $answerVariations = $form->getSubmitValue('answerVariations');
            $this->weighting = $form->getSubmitValue('weighting');

            // Create as many answers as $answerVariations
            for ($j = 0; $j < $answerVariations; $j++) {
                $auxAnswer = $answer;
                $auxFormula = $formula;
                $nb = preg_match_all('/\[[^\]]*\]/', $auxAnswer, $blanks);
                if ($nb > 0) {
                    for ($i = 0; $i < $nb; $i++) {
                        $blankItem = $blanks[0][$i];

                        // take random float values when one or both edge values have a decimal point
                        $randomValue =
                            (strpos($lowestValues[$i], '.') !== false ||
                            strpos($highestValues[$i], '.') !== false) ?
                            mt_rand($lowestValues[$i] * 100, $highestValues[$i] * 100) / 100 : mt_rand($lowestValues[$i], $highestValues[$i]);

                        $auxAnswer = str_replace($blankItem, $randomValue, $auxAnswer);
                        $auxFormula = str_replace($blankItem, $randomValue, $auxFormula);
                    }
                    $math = new EvalMath();
                    $result = $math->evaluate($auxFormula);
                    $result = number_format($result, 2, '.', '');
                    // Remove decimal trailing zeros
                    $result = rtrim($result, '0');
                    // If it is an integer (ends in .00) remove the decimal point
                    if (mb_substr($result, -1) === '.') {
                        $result = str_replace('.', '', $result);
                    }
                    // Attach formula
                    $auxAnswer .= " [".$result."]@@".$formula;
                }
                $this->save($exercise);
                $objAnswer = new Answer($this->id);
                $objAnswer->createAnswer($auxAnswer, 1, '', $this->weighting, '');
                $objAnswer->position = [];
                $objAnswer->save();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $header = parent::return_header($exercise, $counter, $score);
        $header .= '<table class="'.$this->question_table_class.'"><tr>';
        $header .= '<th>'.get_lang('Answer').'</th>';
        if ($exercise->showExpectedChoice()) {
            $header .= '<th>'.get_lang('Your choice').'</th>';
            if ($exercise->showExpectedChoiceColumn()) {
                $header .= '<th>'.get_lang('Expected choice').'</th>';
            }
            $header .= '<th>'.get_lang('Status').'</th>';
        }
        $header .= '</tr>';

        return $header;
    }

    /**
     * Returns true if the current question has been attempted to be answered.
     *
     * @return bool
     */
    public function isAnswered()
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $result = Database::select(
            'question_id',
            $table,
            [
                'where' => [
                    'question_id = ? AND c_id = ?' => [
                        $this->id,
                        $this->course['real_id'],
                    ],
                ],
            ]
        );

        return empty($result) ? false : true;
    }
}
