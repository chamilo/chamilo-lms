<?php

/* For licensing terms, see /license.txt */

use Webit\Util\EvalMath\EvalMath;

/**
 *  Class CalculatedAnswer
 *  Calculated question with random variables and a formula.
 */
class CalculatedAnswer extends Question
{
    public $typePicture = 'calculated_answer.png';
    public $explanationLangVar = 'Calculated question';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = CALCULATED_ANSWER;
        $this->isContent = $this->getIsContent();
    }

    public function createAnswersForm($form)
    {
        $defaults = [];
        $defaults['answer'] = get_lang("<table cellspacing=\"0\" cellpadding=\"10\" border=\"1\" width=\"720\" style=\"\" height:=\"\">    <tbody>        <tr>            <td colspan=\"2\">            <h3>Example fill the form activity : calculate the Body Mass Index</h3>            </td>        </tr>        <tr>            <td style=\"text-align: right;\"><strong>Age</strong></td>            <td width=\"75%\" style=\"\">[25] years old</td>        </tr>        <tr>            <td style=\"text-align: right;\"><strong>Sex</strong></td>            <td style=\"\" text-align:=\"\">[M] (M or F)</td>        </tr>        <tr>            <td style=\"text-align: right;\"><strong>Weight</strong></td>            <td style=\"\" text-align:=\"\">95 Kg</td>        </tr>        <tr>            <td style=\"vertical-align: top; text-align: right;\"><strong>Height</strong></td>            <td style=\"vertical-align: top;\">1.81 m</td>        </tr>        <tr>            <td style=\"vertical-align: top; text-align: right;\"><strong>Body Mass Index</strong></td>            <td style=\"vertical-align: top;\">[29] BMI =Weight/Size<sup>2</sup> (Cf.<a href=\"http://en.wikipedia.org/wiki/Body_mass_index\" onclick=\"window.open(this.href,'','resizable=yes,location=yes,menubar=no,scrollbars=yes,status=yes,toolbar=no,fullscreen=no,dependent=no,width=800,height=600,left=40,top=40,status'); return false\"> Wikipedia article</a>)</td>        </tr>    </tbody></table>");

        if (!empty($this->id)) {
            $objAnswer = new Answer($this->id);
            $preArray = explode('@@', $objAnswer->selectAnswer(1));
            $defaults['formula'] = array_pop($preArray);
            $defaults['answer'] = array_shift($preArray);
            $defaults['answer'] = preg_replace('/\[[^\]]*\]/', '[]', $defaults['answer']);
            $defaults['weighting'] = $this->weighting;
        }

        $defaultLow  = 1;
        $defaultHigh = 20;

        $jsVarRanges  = json_encode(get_lang('Variable ranges'));
        $jsRangeValue = json_encode(get_lang('Range value'));

        echo '<script>
(function () {
  "use strict";
  var ANSWER_ID = "answer";
  var VAR_RX = /\\[[^\\]]*\\]/g;
  var DEFAULT_LOW = '.(int)$defaultLow.';
  var DEFAULT_HIGH = '.(int)$defaultHigh.';
  var I18N = { variableRanges: '.$jsVarRanges.', rangeValue: '.$jsRangeValue.' };
  var firstTime = true;
  var randomCache = [];

  function parseTextNumber(textNumber, floatFlag) {
    textNumber = String(textNumber);
    if (textNumber.indexOf(".") > -1) { if (floatFlag) floatFlag.exists = "true"; return parseFloat(textNumber); }
    return parseInt(textNumber, 10);
  }
  function decodeHtml(html) { var d = document.createElement("textarea"); d.innerHTML = html || ""; return d.value.replace(/&nbsp;/g, " "); }
  function readFromTinyMCE() { if (window.tinymce && tinymce.get && tinymce.get(ANSWER_ID)) { try { return tinymce.get(ANSWER_ID).getContent() || ""; } catch (e) {} } return ""; }
  function readFromTextarea() { var ta = document.getElementById(ANSWER_ID); return ta ? (ta.value || "") : ""; }
  function readAnswer() { var val = readFromTinyMCE(); if (!val) { val = readFromTextarea(); } return decodeHtml(val); }
  function uniqPreserveOrder(arr) { var out = [], seen = Object.create(null); for (var i=0;i<arr.length;i++){ var k = arr[i]; if (!seen[k]) { seen[k]=1; out.push(k);} } return out; }

  function renderRanges(blanks) {
    var root = document.getElementById("blanks_weighting"); if (!root) return;
    var html = "<div class=\\"form-group\\">";
    html += "<label class=\\"col-sm-2\\">" + I18N.variableRanges + "</label>";
    html += "<div class=\\"col-sm-8\\"><table>";
    if (!blanks || blanks.length === 0) { root.innerHTML = html + "</table></div></div>"; return; }

    for (var i=0; i<blanks.length; i++) {
      var lowEl  = document.getElementById("lowestValue["+i+"]");
      var highEl = document.getElementById("highestValue["+i+"]");
      var lowVal  = lowEl  ? lowEl.value  : (DEFAULT_LOW).toFixed(2);
      var highVal = highEl ? highEl.value : (DEFAULT_HIGH).toFixed(2);

      if (typeof randomCache[i] === "undefined") {
        var f = { exists: "false" };
        var lo = parseTextNumber(lowVal,  f);
        var hi = parseTextNumber(highVal, f);
        var r  = Math.random() * (hi - lo) + lo;
        randomCache[i] = (f.exists === "true") ? parseFloat(r).toFixed(2) : parseInt(r, 10);
      }

      html += "<tr>";
      html += "<td><label>"+ blanks[i] +"</label></td>";
      html += "<td><input class=\\"span1\\" style=\\"margin-left:0em;\\" size=\\"5\\" value=\\""+lowVal+"\\" type=\\"text\\" id=\\"lowestValue["+i+"]\\" name=\\"lowestValue["+i+"]\\" onblur=\\"updateRandomValue(this)\\"/></td>";
      html += "<td><input class=\\"span1\\" style=\\"margin-left:0em;width:80px;\\" size=\\"5\\" value=\\""+highVal+"\\" type=\\"text\\" id=\\"highestValue["+i+"]\\" name=\\"highestValue["+i+"]\\" onblur=\\"updateRandomValue(this)\\"/></td>";
      html += "<td><label class=\\"span3\\" id=\\"randomValue["+i+"]\\">" + I18N.rangeValue + ": " + randomCache[i] + "</label></td>";
      html += "</tr>";
    }
    root.innerHTML = html + "</table></div></div>";
  }

  window.updateRandomValue = function(element) {
    var idxStr = (element.name).match(/\\[[^\\]]*\\]/g);
    var floatFlag = { exists: "false" };
    var lo = parseTextNumber(document.getElementById("lowestValue"+idxStr).value,  floatFlag);
    var hi = parseTextNumber(document.getElementById("highestValue"+idxStr).value, floatFlag);
    var r  = Math.random() * (hi - lo) + lo;
    var out = (floatFlag.exists === "true") ? parseFloat(r).toFixed(2) : parseInt(r, 10);
    var rv = document.getElementById("randomValue"+idxStr);
    if (rv) rv.innerHTML = I18N.rangeValue + ": " + out;
  };

  function updateBlanks() {
    var txt = firstTime ? readFromTextarea() : readAnswer();
    var matches = txt.match(VAR_RX) || [];
    var blanks = uniqPreserveOrder(matches);
    renderRanges(blanks);
    firstTime = false;
  }

  function attachTinyMCEListeners() {
    var editor = (window.tinymce && tinymce.get) ? tinymce.get(ANSWER_ID) : null;
    if (editor) {
      editor.on("keyup", updateBlanks);
      editor.on("SetContent", updateBlanks);
      editor.on("ExecCommand", updateBlanks);
      editor.on("Paste", updateBlanks);
      editor.on("Change", updateBlanks);
    } else {
      setTimeout(attachTinyMCEListeners, 100);
    }
  }

  function attachTextareaListeners() {
    var ta = document.getElementById(ANSWER_ID);
    if (ta && ta.addEventListener) {
      ta.addEventListener("keyup", updateBlanks);
      ta.addEventListener("input", updateBlanks);
    }
  }

  function boot(){ attachTinyMCEListeners(); attachTextareaListeners(); updateBlanks(); }
  if (document.readyState === "loading") { document.addEventListener("DOMContentLoaded", boot); } else { boot(); }
  window.onload = updateBlanks;
})();
</script>';

        // Editor
        $form->addElement(
            'label',
            null,
            '<br /><br />'.get_lang('Please type your text below').', '.get_lang('and').' '.get_lang('use square brackets [...] to define one or more blanks')
        );
        $form->addHtmlEditor(
            'answer',
            '',
            true,
            false,
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
        $form->addRule('answer', get_lang('Please define at least one blank with the selected marker'), 'regex', '/\[[^\]]+\]/');
        $form->applyFilter('answer', 'attr_on_filter');

        $form->addElement('label', null, get_lang('If you want only integer values write both limits without decimals'));
        $form->addHtml('<div id="blanks_weighting"></div>');

        $notationListButton = Display::url(
            get_lang('Formula notation'),
            api_get_path(WEB_CODE_PATH).'exercise/evalmathnotation.php',
            ['class' => 'btn btn--info ajax', 'data-title' => get_lang('Formula notation'), '_target' => '_blank']
        );
        $form->addElement('label', null, $notationListButton);

        $form->addText(
            'formula',
            [get_lang('Formula'), get_lang('Formula sample: sqrt( [x] / [y] ) * ( e ^ ( ln(pi) ) )')],
            true,
            ['id' => 'formula']
        );
        $form->addRule('formula', get_lang('Please, write the formula'), 'required');

        $form->addElement('text', 'weighting', get_lang('Score'), ['id' => 'weighting']);
        $form->setDefaults(['weighting' => '10']);

        $form->addElement('text', 'answerVariations', get_lang('Question variations'));
        $form->addRule('answerVariations', get_lang('Question variations'), 'required');
        $form->setDefaults(['answerVariations' => '1']);

        global $text;
        $form->addButtonSave($text, 'submitQuestion');

        if (!empty($this->id)) {
            $form->setDefaults($defaults);
        } else {
            if (1 == $this->isContent) {
                $form->setDefaults($defaults);
            }
        }
    }

    public function processAnswersCreation($form, $exercise)
    {
        // Only create answers if this question hasn't been answered yet
        if (!self::isAnswered()) {
            $table = Database::get_course_table(TABLE_QUIZ_ANSWER);

            $answer           = (string) $form->getSubmitValue('answer');
            $formula          = (string) $form->getSubmitValue('formula');
            $lowestValues     = (array) ($form->getSubmitValue('lowestValue')  ?? []);
            $highestValues    = (array) ($form->getSubmitValue('highestValue') ?? []);
            $answerVariations = max(1, (int) $form->getSubmitValue('answerVariations'));
            $this->weighting  = (float) $form->getSubmitValue('weighting');

            // Ensure the question entity exists and $this->id is set
            $this->save($exercise);

            // Clean up ONLY by question_id (C2 schema has no c_id in c_quiz_answer)
            Database::delete($table, ['question_id = ?' => [$this->id]]);

            // Must contain at least one [...] placeholder
            if (!preg_match('/\[[^\]]*\]/', $answer)) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('Please define at least one blank with the selected marker'),
                        'error'
                    )
                );
                return;
            }

            // Build N variations and persist into c_quiz_answer
            $ans = new Answer($this->id, 0, $exercise, false);

            for ($j = 0; $j < $answerVariations; $j++) {
                $auxAnswer  = $answer;
                $auxFormula = $formula;

                $nb = preg_match_all('/\[[^\]]*\]/', $auxAnswer, $blanks);
                if ($nb > 0) {
                    for ($i = 0; $i < $nb; $i++) {
                        // Infer range and number type (int/float) from bounds
                        $lo = (string) (($lowestValues[$i]  ?? '') !== '' ? $lowestValues[$i]  : '1');
                        $hi = (string) (($highestValues[$i] ?? '') !== '' ? $highestValues[$i] : '20');
                        $isFloat = (strpos($lo, '.') !== false) || (strpos($hi, '.') !== false);

                        // Generate random within [lo, hi]
                        $rand = $isFloat
                            ? mt_rand((int) round(((float) $lo) * 100), (int) round(((float) $hi) * 100)) / 100
                            : mt_rand((int) $lo, (int) $hi);

                        // Replace both in the visible text and the formula
                        $auxAnswer  = str_replace($blanks[0][$i], (string) $rand, $auxAnswer);
                        $auxFormula = str_replace($blanks[0][$i], (string) $rand, $auxFormula);
                    }

                    // Evaluate the formula
                    $math   = new EvalMath();
                    $result = (float) $math->evaluate($auxFormula);

                    // Normalize result: keep up to 2 decimals, trim trailing zeros and dot
                    $result = rtrim(rtrim(number_format($result, 2, '.', ''), '0'), '.');

                    // Append the computed result at the end (visible) and keep the original formula after @@
                    // Example final string: "... [42]@@sqrt([x]/[y])"
                    $auxAnswer .= ' ['.$result.']@@'.$formula;
                }

                $ans->createAnswer(
                    $auxAnswer,
                    1,
                    '',
                    (float) $this->weighting,
                    $j + 1
                );
            }

            $ans->save();
        }
    }

    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $header = parent::return_header($exercise, $counter, $score);
        $header .= '<table class="'.$this->questionTableClass.'"><tr>';
        $header .= '<th>'.get_lang('Answer').'</th>';
        if ($exercise->showExpectedChoice()) {
            $header .= '<th>'.get_lang('Your choice').'</th>';
            if ($exercise->showExpectedChoiceColumn()) {
                $header .= '<th>'.get_lang('Expected choice').'</th>';
            }
            $header .= '<th class="text-center">'.get_lang('Status').'</th>';
        }
        $header .= '</tr>';

        return $header;
    }

    /**
     * Returns true if the current question has been attempted to be answered.
     */
    public function isAnswered(): bool
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $result = Database::select(
            'question_id',
            $table,
            ['where' => ['question_id = ?' => [$this->id]]]
        );

        return !empty($result);
    }
}
