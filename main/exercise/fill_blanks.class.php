<?php
/* For licensing terms, see /license.txt */

/**
 *	Class FillBlanks
 *
 *	@author Eric Marguin
 * 	@author Julio Montoya multiple fill in blank option added
 *	@package chamilo.exercise
 **/
class FillBlanks extends Question
{
    public static $typePicture = 'fill_in_blanks.png';
    public static $explanationLangVar = 'FillBlanks';

    const FILL_THE_BLANK_STANDARD = 0;
    const FILL_THE_BLANK_MENU = 1;
    const FILL_THE_BLANK_SEVERAL_ANSWER = 2;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = FILL_IN_BLANKS;
        $this->isContent = $this->getIsContent();
    }

    /**
     * function which redefines Question::createAnswersForm
     * @param FormValidator $form
     */
    public function createAnswersForm($form)
    {
        $fillBlanksAllowedSeparator = self::getAllowedSeparator();
        $defaults = array();

        if (!empty($this->id)) {
            $objectAnswer = new Answer($this->id);
            $answer = $objectAnswer->selectAnswer(1);
            $listAnswersInfo = FillBlanks::getAnswerInfo($answer);

            if ($listAnswersInfo["switchable"]) {
                $defaults['multiple_answer'] = 1;
            } else {
                $defaults['multiple_answer'] = 0;
            }
            //take the complete string except after the last '::'
            $defaults['answer'] = $listAnswersInfo["text"];
            $defaults['select_separator'] = $listAnswersInfo["blankseparatornumber"];
            $blanksepartornumber = $listAnswersInfo["blankseparatornumber"];
        } else {
            $defaults['answer'] = get_lang('DefaultTextInBlanks');
            $defaults['select_separator'] = 0;
            $blanksepartornumber = 0;
        }

        $blankSeparatorStart = self::getStartSeparator($blanksepartornumber);
        $blankSeparatorEnd = self::getEndSeparator($blanksepartornumber);

        $setValues = null;

        if (isset($a_weightings) && count($a_weightings) > 0) {
            foreach ($a_weightings as $i => $weighting) {
                $setValues .= 'document.getElementById("weighting['.$i.']").value = "'.$weighting.'";';
            }
        }
        // javascript
        echo '<script>

            var blankSeparatortStart = "'.$blankSeparatorStart.'";
            var blankSeparatortEnd = "'.$blankSeparatorEnd.'";
            var blankSeparatortStartRegexp = getBlankSeparatorRegexp(blankSeparatortStart);
            var blankSeparatortEndRegexp = getBlankSeparatorRegexp(blankSeparatortEnd);

            CKEDITOR.on("instanceCreated", function(e) {
                if (e.editor.name === "answer") {
                    e.editor.on("change", updateBlanks);
                }
            });

            var firstTime = true;

            function updateBlanks()
            {
                if (firstTime) {
                    var field = document.getElementById("answer");
                    var answer = field.value;
                } else {
                    var answer = CKEDITOR.instances["answer"].getData();
                }

                // disable the save button, if not blanks have been created
                $("button").attr("disabled", "disabled");
                $("#defineoneblank").show();

                var blanksRegexp = "/"+blankSeparatortStartRegexp+"[^"+blankSeparatortStartRegexp+"]*"+blankSeparatortEndRegexp+"/g";

                var blanks = answer.match(eval(blanksRegexp));
                var fields = "<div class=\"form-group \">";
                fields += "<label class=\"col-sm-2 control-label\">'.get_lang('Weighting').'</label>";
                fields += "<div class=\"col-sm-8\">";
                fields += "<table>";
                fields += "<tr><th style=\"padding:0 20px\">'.get_lang("WordTofind").'</th><th style=\"padding:0 20px\">'.get_lang("QuestionWeighting").'</th><th style=\"padding:0 20px\">'.get_lang("BlankInputSize").'</th></tr>";

                if (blanks != null) {
                    for (var i=0 ; i < blanks.length ; i++){
                        // remove forbidden characters that causes bugs
                        blanks[i] = removeForbiddenChars(blanks[i]);
                        // trim blanks between brackets
                        blanks[i] = trimBlanksBetweenSeparator(blanks[i], blankSeparatortStart, blankSeparatortEnd);

                        // if the word is empty []
                        if (blanks[i] == blankSeparatortStartRegexp+blankSeparatortEndRegexp) {
                            break;
                        }
                        // get input size
                        var lainputsize = 200;
                        var lainputsizetrue = 200;
                        if ($("#samplesize\\\["+i+"\\\]").width()) {
                        // this is a weird patch to avoid to reduce the size of input blank when you are writing in the ckeditor.
                            lainputsize = $("#samplesize\\\["+i+"\\\]").width();
                            lainputsizetrue = $("#samplesize\\\["+i+"\\\]").width() + 9;
                        }

                        if (document.getElementById("weighting["+i+"]")) {
                            var value = document.getElementById("weighting["+i+"]").value;
                        } else {
                            var value = "10";
                        }
                        fields += "<tr>";
                        fields += "<td>"+blanks[i]+"</td>";
                        fields += "<td><input style=\"width:35px\" value=\""+value+"\" type=\"text\" id=\"weighting["+i+"]\" name=\"weighting["+i+"]\" /></td>";
                        fields += "<td>";
                        fields += "<input type=\"button\" value=\"-\" onclick=\"changeInputSize(-1, "+i+")\">";
                        fields += "<input type=\"button\" value=\"+\" onclick=\"changeInputSize(1, "+i+")\">";
                        fields += "<input value=\""+blanks[i].substr(1, blanks[i].length - 2)+"\" style=\"width:"+lainputsizetrue+"px\" disabled=disabled id=\"samplesize["+i+"]\"/>";
                        fields += "<input type=\"hidden\" id=\"sizeofinput["+i+"]\" name=\"sizeofinput["+i+"]\" value=\""+lainputsize+"\" \"/>";
                        fields += "</td>";
                        fields += "</tr>";
                        // enable the save button
                        $("button").removeAttr("disabled");
                        $("#defineoneblank").hide();
                    }
                }
                document.getElementById("blanks_weighting").innerHTML = fields + "</table></div></div>";
                if (firstTime) {
                    firstTime = false;
                ';

        if (isset($listAnswersInfo) && count($listAnswersInfo["tabweighting"]) > 0) {
            foreach ($listAnswersInfo["tabweighting"] as $i => $weighting) {
                echo 'document.getElementById("weighting['.$i.']").value = "'.$weighting.'";';
            }
            foreach ($listAnswersInfo["tabinputsize"] as $i => $sizeOfInput) {
                echo 'document.getElementById("sizeofinput['.$i.']").value = "'.$sizeOfInput.'";';
                echo '$("#samplesize\\\['.$i.'\\\]").width('.$sizeOfInput.');';
            }
        }

        echo '}
            }
            window.onload = updateBlanks;

            function getInputSize() {
                var outTabSize = new Array();
                $("input").each(function() {
                    if ($(this).attr("id") && $(this).attr("id").match(/samplesize/)) {
                        var tabidnum = $(this).attr("id").match(/\d+/);
                        var idnum = tabidnum[0];
                        var thewidth = $(this).next().attr("value");
                        tabInputSize[idnum] = thewidth;
                    }
                });
            }

            function changeInputSize(inCoef, inIdNum)
            {
                var currentWidth = $("#samplesize\\\["+inIdNum+"\\\]").width();
                var newWidth = currentWidth + inCoef * 20;
                newWidth = Math.max(20, newWidth);
                newWidth = Math.min(newWidth, 600);
                $("#samplesize\\\["+inIdNum+"\\\]").width(newWidth);
                $("#sizeofinput\\\["+inIdNum+"\\\]").attr("value", newWidth);
            }

            function removeForbiddenChars(inTxt) {
                outTxt = inTxt;

                outTxt = outTxt.replace(/&quot;/g, ""); // remove the   char
                outTxt = outTxt.replace(/\x22/g, ""); // remove the   char
                outTxt = outTxt.replace(/"/g, ""); // remove the   char
                outTxt = outTxt.replace(/\\\\/g, ""); // remove the \ char
                outTxt = outTxt.replace(/&nbsp;/g, " ");
                outTxt = outTxt.replace(/^ +/, "");
                outTxt = outTxt.replace(/ +$/, "");
                return outTxt;
            }

            function changeBlankSeparator()
            {
                var separatorNumber = $("#select_separator").val();
                var tabSeparator = getSeparatorFromNumber(separatorNumber);
                blankSeparatortStart = tabSeparator[0];
                blankSeparatortEnd = tabSeparator[1];
                blankSeparatortStartRegexp = getBlankSeparatorRegexp(blankSeparatortStart);
                blankSeparatortEndRegexp = getBlankSeparatorRegexp(blankSeparatortEnd);
                updateBlanks();
            }

            // this function is the same than the PHP one
            // if modify it modify the php one escapeForRegexp
            function getBlankSeparatorRegexp(inTxt)
            {
                var tabSpecialChar = new Array(".", "+", "*", "?", "[", "^", "]", "$", "(", ")",
                    "{", "}", "=", "!", "<", ">", "|", ":", "-", ")");
                for (var i=0; i < tabSpecialChar.length; i++) {
                    if (inTxt == tabSpecialChar[i]) {
                        return "\\\"+inTxt;
                    }
                }
                return inTxt;
            }

            // this function is the same than the PHP one
            // if modify it modify the php one getAllowedSeparator
            function getSeparatorFromNumber(innumber)
            {
                tabSeparator = new Array();
                tabSeparator[0] = new Array("[", "]");
                tabSeparator[1] = new Array("{", "}");
                tabSeparator[2] = new Array("(", ")");
                tabSeparator[3] = new Array("*", "*");
                tabSeparator[4] = new Array("#", "#");
                tabSeparator[5] = new Array("%", "%");
                tabSeparator[6] = new Array("$", "$");
                return tabSeparator[innumber];
            }

            function trimBlanksBetweenSeparator(inTxt, inSeparatorStart, inSeparatorEnd)
            {
                // blankSeparatortStartRegexp
                // blankSeparatortEndRegexp
                var result = inTxt
                result = result.replace(inSeparatorStart, "");
                result = result.replace(inSeparatorEnd, "");
                result = result.trim();
                return inSeparatorStart+result+inSeparatorEnd;
            }
        </script>';

        // answer
        $form->addElement('label', null, '<br /><br />'.get_lang('TypeTextBelow').', '.get_lang('And').' '.get_lang('UseTagForBlank'));
        $form->addElement(
            'html_editor',
            'answer',
            Display::return_icon('fill_field.png'),
            ['id' => 'answer', 'onkeyup' => "javascript: updateBlanks(this);"],
            array('ToolbarSet' => 'TestQuestionDescription')
        );
        $form->addRule('answer',get_lang('GiveText'),'required');

        //added multiple answers
        $form->addElement('checkbox','multiple_answer','', get_lang('FillInBlankSwitchable'));
        $form->addElement(
            'select',
            'select_separator',
            get_lang("SelectFillTheBlankSeparator"),
            self::getAllowedSeparatorForSelect(),
            ' id="select_separator"   style="width:150px" onchange="changeBlankSeparator()" '
        );
        $form->addElement(
            'label',
            null,
            '<input type="button" onclick="updateBlanks()" value="'.get_lang('RefreshBlanks').'" class="btn btn-default" />'
        );
        $form->addElement('html','<div id="blanks_weighting"></div>');

        global $text;
        // setting the save button here and not in the question class.php
        $form->addElement('html','<div id="defineoneblank" style="color:#D04A66; margin-left:160px">'.get_lang('DefineBlanks').'</div>');
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
     * Function which creates the form to create/edit the answers of the question
     * @param FormValidator $form
     */
    public function processAnswersCreation($form)
    {
        $answer = $form->getSubmitValue('answer');
        // Due the ckeditor transform the elements to their HTML value

        //$answer = api_html_entity_decode($answer, ENT_QUOTES, $charset);
        //$answer = htmlentities(api_utf8_encode($answer));

        // remove the :: eventually written by the user
        $answer = str_replace('::', '', $answer);

        // remove starting and ending space and &nbsp;
        $answer = api_preg_replace("/\xc2\xa0/", " ", $answer);

        // start and end separator
        $blankStartSeparator = self::getStartSeparator($form->getSubmitValue('select_separator'));
        $blankEndSeparator = self::getEndSeparator($form->getSubmitValue('select_separator'));
        $blankStartSeparatorRegexp = self::escapeForRegexp($blankStartSeparator);
        $blankEndSeparatorRegexp = self::escapeForRegexp($blankEndSeparator);

        // remove spaces at the beginning and the end of text in square brackets
        $answer = preg_replace_callback(
            "/".$blankStartSeparatorRegexp."[^]]+".$blankEndSeparatorRegexp."/",
            function ($matches) use ($blankStartSeparator, $blankEndSeparator) {
                $matchingResult = $matches[0];
                $matchingResult = trim($matchingResult, $blankStartSeparator);
                $matchingResult = trim($matchingResult, $blankEndSeparator);
                $matchingResult = trim($matchingResult);
                // remove forbidden chars
                $matchingResult = str_replace("/\\/", "", $matchingResult);
                $matchingResult = str_replace('/"/', "", $matchingResult);

                return $blankStartSeparator.$matchingResult.$blankEndSeparator;
            },
            $answer
        );

        // get the blanks weightings
        $nb = preg_match_all(
            '/'.$blankStartSeparatorRegexp.'[^'.$blankStartSeparatorRegexp.']*'.$blankEndSeparatorRegexp.'/',
            $answer,
            $blanks
        );

        if (isset($_GET['editQuestion'])) {
            $this->weighting = 0;
        }

        /* if we have some [tobefound] in the text
        build the string to save the following in the answers table
        <p>I use a [computer] and a [pen].</p>
        becomes
        <p>I use a [computer] and a [pen].</p>::100,50:100,50@1
            ++++++++-------**
            --- -- --- -- -
            A B  (C) (D)(E)
        +++++++ : required, weighting of each words
        ------- : optional, input width to display, 200 if not present
        ** : equal @1 if "Allow answers order switches" has been checked, @ otherwise
        A : weighting for the word [computer]
        B : weighting for the word [pen]
        C : input width for the word [computer]
        D : input width for the word [pen]
        E : equal @1 if "Allow answers order switches" has been checked, @ otherwise
        */
        if ($nb > 0) {
            $answer .= '::';
            // weighting
            for ($i=0; $i < $nb; ++$i) {
                // enter the weighting of word $i
                $answer .= $form->getSubmitValue('weighting['.$i.']');
                // not the last word, add ","
                if ($i != $nb - 1) {
                    $answer .= ",";
                }
                // calculate the global weighting for the question
                $this -> weighting += $form->getSubmitValue('weighting['.$i.']');
            }

            // input width
            $answer .= ":";
            for ($i=0; $i < $nb; ++$i) {
                // enter the width of input for word $i
                $answer .= $form->getSubmitValue('sizeofinput['.$i.']');
                // not the last word, add ","
                if ($i != $nb - 1) {
                    $answer .= ",";
                }
            }
        }

        // write the blank separator code number
        // see function getAllowedSeparator
        /*
            0 [...]
            1 {...}
            2 (...)
            3 *...*
            4 #...#
            5 %...%
            6 $...$
         */
        $answer .= ":".$form->getSubmitValue('select_separator');

        // Allow answers order switches
        $is_multiple = $form -> getSubmitValue('multiple_answer');
        $answer.= '@'.$is_multiple;

        $this->save();
        $objAnswer = new Answer($this->id);
        $objAnswer->createAnswer($answer, 0, '', 0, 1);
        $objAnswer->save();
    }

    /**
     * @param null $feedback_type
     * @param null $counter
     * @param null $score
     * @return string
     */
    public function return_header($feedback_type = null, $counter = null, $score = null)
    {
        $header = parent::return_header($feedback_type, $counter, $score);
        $header .= '<table class="'.$this->question_table_class .'">
            <tr>
                <th>'.get_lang("Answer").'</th>
            </tr>';

        return $header;
    }

    /**
     * @param string $separatorStartRegexp
     * @param string $separatorEndRegexp
     * @param string $correctItemRegexp
     * @param integer $questionId
     * @param $correctItem
     * @param $attributes
     * @param string $answer
     * @param $listAnswersInfo
     * @param boolean $displayForStudent
     * @param integer $inBlankNumber
     * @return string
     */
    public static function getFillTheBlankHtml(
        $separatorStartRegexp,
        $separatorEndRegexp,
        $correctItemRegexp,
        $questionId,
        $correctItem,
        $attributes,
        $answer,
        $listAnswersInfo,
        $displayForStudent,
        $inBlankNumber
    ) {
        $result = "";
        $inTabTeacherSolution = $listAnswersInfo['tabwords'];
        $inTeacherSolution = $inTabTeacherSolution[$inBlankNumber];
        switch (self::getFillTheBlankAnswerType($inTeacherSolution)) {
            case self::FILL_THE_BLANK_MENU:
                $selected = '';
                // the blank menu
                $optionMenu = '';
                // display a menu from answer separated with |
                // if display for student, shuffle the correct answer menu
                $listMenu = self::getFillTheBlankMenuAnswers($inTeacherSolution, $displayForStudent);
                $result .= '<select name="choice['.$questionId.'][]">';
                for ($k=0; $k < count($listMenu); $k++) {
                    $selected = "";
                    if ($correctItem == $listMenu[$k]) {
                        $selected = " selected=selected ";
                    }
                    // if in teacher view, display the first item by default, which is the right answer
                    if ($k==0 && !$displayForStudent) {
                        $selected = " selected=selected ";
                    }
                    $optionMenu .= '<option '.$selected.' value="'.$listMenu[$k].'">'.$listMenu[$k].'</option>';
                }
                if ($selected == "") {
                    // no good answer have been found...
                    $selected = " selected=selected ";
                }
                $result .= "<option $selected value=''>--</option>";
                $result .= $optionMenu;
                $result .= '</select>';
                break;
            case self::FILL_THE_BLANK_SEVERAL_ANSWER:
                //no break
            case self::FILL_THE_BLANK_STANDARD:
            default:
                $result = Display::input('text', "choice[$questionId][]", $correctItem, $attributes);
                break;
        }

        return $result;
    }

    /**
     * Return an array with the different choices available
     * when the answers between bracket show as a menu
     * @param string $correctAnswer
     * @param bool $displayForStudent true if we want to shuffle the choices of the menu for students
     *
     * @return array
     */
    public static function getFillTheBlankMenuAnswers($correctAnswer, $displayForStudent)
    {
        // if $inDisplayForStudent, then shuffle the result array
        $listChoises = api_preg_split("/\|/", $correctAnswer);
        if ($displayForStudent) {
            shuffle($listChoises);
        }

        return $listChoises;
    }

    /**
     * Return the array index of the student answer
     * @param string $correctAnswer the menu Choice1|Choice2|Choice3
     * @param string $studentAnswer the student answer must be Choice1 or Choice2 or Choice3
     *
     * @return int  in the example 0 1 or 2 depending of the choice of the student
     */
    public static function getFillTheBlankMenuAnswerNum($correctAnswer, $studentAnswer)
    {
        $listChoices = self::getFillTheBlankMenuAnswers($correctAnswer, false);
        foreach ($listChoices as $num => $value) {
            if ($value == $studentAnswer) {
                return $num;
            }
        }

        // should not happened, because student choose the answer in a menu of possible answers
        return -1;
    }


    /**
     * Return the possible answer if the answer between brackets is a multiple choice menu
     * @param string $correctAnswer
     *
     * @return array
     */
    public static function getFillTheBlankSeveralAnswers($correctAnswer)
    {
        // is answer||Answer||response||Response , mean answer or Answer ...
        $listSeveral = api_preg_split("/\|\|/", $correctAnswer);

        return $listSeveral;
    }

    /**
     * Return true if student answer is right according to the correctAnswer
     * it is not as simple as equality, because of the type of Fill The Blank question
     * eg : studentAnswer = 'Un' and correctAnswer = 'Un||1||un'
     * @param string $studentAnswer [studentanswer] of the info array of the answer field
     * @param string $correctAnswer [tabwords] of the info array of the answer field
     *
     * @return bool
     */
    public static function isGoodStudentAnswer($studentAnswer, $correctAnswer)
    {
        switch (self::getFillTheBlankAnswerType($correctAnswer)) {
            case self::FILL_THE_BLANK_MENU:
                $listMenu = self::getFillTheBlankMenuAnswers($correctAnswer, false);
                $result = $listMenu[0] == $studentAnswer;
                break;
            case self::FILL_THE_BLANK_SEVERAL_ANSWER:
                // the answer must be one of the choice made
                $listSeveral = self::getFillTheBlankSeveralAnswers($correctAnswer);
                $result = in_array($studentAnswer, $listSeveral);
                break;
            case self::FILL_THE_BLANK_STANDARD:
            default:
                $result = $studentAnswer == $correctAnswer;
                break;
        }

        return $result;
    }

    /**
     * @param string $correctAnswer
     *
     * @return int
     */
    public static function getFillTheBlankAnswerType($correctAnswer)
    {
        if (api_strpos($correctAnswer, "|") && !api_strpos($correctAnswer, "||")) {
            return self::FILL_THE_BLANK_MENU;
        } elseif (api_strpos($correctAnswer, "||")) {
            return self::FILL_THE_BLANK_SEVERAL_ANSWER;
        } else {
            return self::FILL_THE_BLANK_STANDARD;
        }
    }

    /**
     * Return information about the answer
     * @param string $userAnswer the text of the answer of the question
     * @param bool   $isStudentAnswer true if it's a student answer false the empty question model
     *
     * @return array of information about the answer
     */
    public static function getAnswerInfo($userAnswer = "", $isStudentAnswer = false)
    {
        $listAnswerResults = array();
        $listAnswerResults['text'] = "";
        $listAnswerResults['wordsCount'] = 0;
        $listAnswerResults['tabwordsbracket'] = array();
        $listAnswerResults['tabwords'] = array();
        $listAnswerResults['tabweighting'] = array();
        $listAnswerResults['tabinputsize'] = array();
        $listAnswerResults['switchable'] = "";
        $listAnswerResults['studentanswer'] = array();
        $listAnswerResults['studentscore'] = array();
        $listAnswerResults['blankseparatornumber'] = 0;
        $listDoubleColon = array();

        api_preg_match("/(.*)::(.*)$/s", $userAnswer, $listResult);

        if (count($listResult) < 2) {
            $listDoubleColon[] = '';
            $listDoubleColon[] = '';
        } else {
            $listDoubleColon[] = $listResult[1];
            $listDoubleColon[] = $listResult[2];
        }

        $listAnswerResults['systemstring'] = $listDoubleColon[1];

        // make sure we only take the last bit to find special marks
        $listArobaseSplit = explode('@', $listDoubleColon[1]);

        if (count($listArobaseSplit) < 2) {
            $listArobaseSplit[1] = "";
        }

        // take the complete string except after the last '::'
        $listDetails = explode(":", $listArobaseSplit[0]);

        // < number of item after the ::[score]:[size]:[separator_id]@ , here there are 3
        if (count($listDetails) < 3) {
            $listWeightings = explode(',', $listDetails[0]);
            $listSizeOfInput = array();
            for ($i=0; $i < count($listWeightings); $i++) {
                $listSizeOfInput[] = 200;
            }
            $blankSeparatorNumber = 0;    // 0 is [...]
        } else {
            $listWeightings = explode(',', $listDetails[0]);
            $listSizeOfInput = explode(',', $listDetails[1]);
            $blankSeparatorNumber = $listDetails[2];
        }

        $listAnswerResults['text'] = $listDoubleColon[0];
        $listAnswerResults['tabweighting'] = $listWeightings;
        $listAnswerResults['tabinputsize'] = $listSizeOfInput;
        $listAnswerResults['switchable'] = $listArobaseSplit[1];
        $listAnswerResults['blankseparatorstart'] = self::getStartSeparator($blankSeparatorNumber);
        $listAnswerResults['blankseparatorend'] = self::getEndSeparator($blankSeparatorNumber);
        $listAnswerResults['blankseparatornumber'] = $blankSeparatorNumber;

        $blankCharStart = self::getStartSeparator($blankSeparatorNumber);
        $blankCharEnd = self::getEndSeparator($blankSeparatorNumber);
        $blankCharStartForRegexp = self::escapeForRegexp($blankCharStart);
        $blankCharEndForRegexp = self::escapeForRegexp($blankCharEnd);

        // get all blanks words
        $listAnswerResults['wordsCount'] = api_preg_match_all(
            '/'.$blankCharStartForRegexp.'[^'.$blankCharEndForRegexp.']*'.$blankCharEndForRegexp.'/',
            $listDoubleColon[0],
            $listWords
        );

        if ($listAnswerResults['wordsCount'] > 0) {
            $listAnswerResults['tabwordsbracket'] = $listWords[0];
            // remove [ and ] in string
            array_walk(
                $listWords[0],
                function (&$value, $key, $tabBlankChar) {
                    $trimChars = "";
                    for ($i=0; $i < count($tabBlankChar); $i++) {
                        $trimChars .= $tabBlankChar[$i];
                    }
                    $value = trim($value, $trimChars);
                },
                array($blankCharStart, $blankCharEnd)
            );
            $listAnswerResults['tabwords'] = $listWords[0];
        }

        // get all common words
        $commonWords = api_preg_replace(
            '/'.$blankCharStartForRegexp.'[^'.$blankCharEndForRegexp.']*'.$blankCharEndForRegexp.'/',
            "::",
            $listDoubleColon[0]
        );

        // if student answer, the second [] is the student answer,
        // the third is if student scored or not
        $listBrackets = array();
        $listWords =  array();

        if ($isStudentAnswer) {
            for ($i=0; $i < count($listAnswerResults['tabwords']); $i++) {
                $listBrackets[] = $listAnswerResults['tabwordsbracket'][$i];
                $listWords[] = $listAnswerResults['tabwords'][$i];
                if ($i+1 < count($listAnswerResults['tabwords'])) {
                    // should always be
                    $i++;
                }
                $listAnswerResults['studentanswer'][] = $listAnswerResults['tabwords'][$i];
                if ($i+1 < count($listAnswerResults['tabwords'])) {
                    // should always be
                    $i++;
                }
                $listAnswerResults['studentscore'][] = $listAnswerResults['tabwords'][$i];
            }
            $listAnswerResults['tabwords'] = $listWords;
            $listAnswerResults['tabwordsbracket'] = $listBrackets;

            // if we are in student view, we've got 3 times :::::: for common words
            $commonWords = api_preg_replace("/::::::/", "::", $commonWords);
        }

        $listAnswerResults['commonwords'] = explode("::", $commonWords);

        return $listAnswerResults;
    }

    /**
    * Return an array of student state answers for fill the blank questions
    * for each students that answered the question
    * -2  : didn't answer
    * -1  : student answer is wrong
    *  0  : student answer is correct
    * >0  : for fill the blank question with choice menu, is the index of the student answer (right answer indice is 0)
    *
    * @param integer $testId
    * @param integer $questionId
    * @param $studentsIdList
    * @param string $startDate
    * @param string $endDate
    * @param bool $useLastAnswerredAttempt
    * @return array
    * (
    *     [student_id] => Array
    *         (
    *             [first fill the blank for question] => -1
    *             [second fill the blank for question] => 2
    *             [third fill the blank for question] => -1
    *         )
    * )
    */
    public static function getFillTheBlankTabResult(
        $testId,
        $questionId,
        $studentsIdList,
        $startDate,
        $endDate,
        $useLastAnswerredAttempt = true
    ) {
       $tblTrackEAttempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
       $tblTrackEExercise = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
       $courseId = api_get_course_int_id();
        // request to have all the answers of student for this question
       // student may have doing it several time
       // student may have not answered the bracket id, in this case, is result of the answer is empty

       // we got the less recent attempt first
       $sql = '
           SELECT * FROM '.$tblTrackEAttempt.' tea
           LEFT JOIN '.$tblTrackEExercise.' tee
           ON tee.exe_id = tea.exe_id
           AND tea.c_id = '.$courseId.'
           AND exe_exo_id = '.$testId.'

           WHERE tee.c_id = '.$courseId.'
           AND question_id = '.$questionId.'
           AND tea.user_id IN ('.implode(',', $studentsIdList).')
           AND tea.tms >= "'.$startDate.'"
           AND tea.tms <= "'.$endDate.'"
           ORDER BY user_id, tea.exe_id;
       ';

       $res = Database::query($sql);
       $tabUserResult = array();
       $bracketNumber = 0;
       // foreach attempts for all students starting with his older attempt
       while ($data = Database::fetch_array($res)) {
           $tabAnswer = FillBlanks::getAnswerInfo($data['answer'], true);

           // for each bracket to find in this question
           foreach ($tabAnswer['studentanswer'] as $bracketNumber => $studentAnswer) {

               if ($tabAnswer['studentanswer'][$bracketNumber] != '') {
                   // student has answered this bracket, cool
                   switch (FillBlanks::getFillTheBlankAnswerType($tabAnswer['tabwords'][$bracketNumber])) {
                       case self::FILL_THE_BLANK_MENU :
                           // get the indice of the choosen answer in the menu
                           // we know that the right answer is the first entry of the menu, ie 0
                           // (remember, menu entries are shuffled when taking the test)
                           $tabUserResult[$data['user_id']][$bracketNumber] = FillBlanks::getFillTheBlankMenuAnswerNum(
                               $tabAnswer['tabwords'][$bracketNumber],
                               $tabAnswer['studentanswer'][$bracketNumber]
                           );
                           break;
                       default :
                           if (FillBlanks::isGoodStudentAnswer($tabAnswer['studentanswer'][$bracketNumber], $tabAnswer['tabwords'][$bracketNumber])) {
                               $tabUserResult[$data['user_id']][$bracketNumber] = 0;   //  right answer
                           } else {
                               $tabUserResult[$data['user_id']][$bracketNumber] = -1;  // wrong answer
                           }
                   }
               } else {
                   // student didn't answer this bracket
                   if ($useLastAnswerredAttempt) {
                       // if we take into account the last answered attempt
                       if (!isset($tabUserResult[$data['user_id']][$bracketNumber])) {
                           $tabUserResult[$data['user_id']][$bracketNumber] = -2;      // not answered
                       }
                   } else {
                       // we take the last attempt, even if the student answer the question before
                       $tabUserResult[$data['user_id']][$bracketNumber] = -2;      // not answered
                   }
               }
           }
       }

       return $tabUserResult;
    }

    /**
     * Return the number of student that give at leat an answer in the fill the blank test
     * @param $resultList
     * @return int
     */
    public static function getNbResultFillBlankAll($resultList)
    {
        $outRes = 0;
        // for each student in group
        foreach($resultList as $userId => $tabValue) {
            $trouve = false;
            // for each bracket, if student has at leat one answer ( choice > -2) then he pass the question
            foreach($tabValue as $i => $choice) {
                if ($choice > -2 && !$trouve) {
                    $outRes++;
                    $trouve = true;
                }
            }
        }
        return $outRes;
    }

    /**
     * Replace the occurrence of blank word with [correct answer][student answer][answer is correct]
     * @param array $listWithStudentAnswer
     *
     * @return string
     */
    public static function getAnswerInStudentAttempt($listWithStudentAnswer)
    {
        $separatorStart = $listWithStudentAnswer['blankseparatorstart'];
        $separatorEnd = $listWithStudentAnswer['blankseparatorend'];
        // lets rebuild the sentence with [correct answer][student answer][answer is correct]
        $result = "";
        for ($i=0; $i < count($listWithStudentAnswer['commonwords']) - 1; $i++) {
            $result .= $listWithStudentAnswer['commonwords'][$i];
            $result .= $listWithStudentAnswer['tabwordsbracket'][$i];
            $result .= $separatorStart.$listWithStudentAnswer['studentanswer'][$i].$separatorEnd;
            $result .= $separatorStart.$listWithStudentAnswer['studentscore'][$i].$separatorEnd;
        }
        $result .= $listWithStudentAnswer['commonwords'][$i];
        $result .= "::";
        // add the system string
        $result .= $listWithStudentAnswer['systemstring'];

        return $result;
    }

    /**
     * This function is the same than the js one above getBlankSeparatorRegexp
     * @param string $inChar
     *
     * @return string
     */
    public static function escapeForRegexp($inChar)
    {
        $listChars = [
            ".",
            "+",
            "*",
            "?",
            "[",
            "^",
            "]",
            "$",
            "(",
            ")",
            "{",
            "}",
            "=",
            "!",
            ">",
            "|",
            ":",
            "-",
            ")",
        ];

        if (in_array($inChar, $listChars)) {
            return "\\".$inChar;
        } else {
            return $inChar;
        }
    }

    /**
     * return $text protected for use in regexp
     * @param string $text
     *
     * @return string
     */
    public static function getRegexpProtected($text)
    {
        $listRegexpCharacters = [
            "/",
            ".",
            "+",
            "*",
            "?",
            "[",
            "^",
            "]",
            "$",
            "(",
            ")",
            "{",
            "}",
            "=",
            "!",
            ">",
            "|",
            ":",
            "-",
            ")",
        ];
        $result = $text;
        for ($i=0; $i < count($listRegexpCharacters); $i++) {
            $result = str_replace($listRegexpCharacters[$i], "\\".$listRegexpCharacters[$i], $result);
        }

        return $result;
    }


    /**
     * This function must be the same than the js one getSeparatorFromNumber above
     * @return array
     */
    public static function getAllowedSeparator()
    {
        $fillBlanksAllowedSeparator = array(
            array('[', ']'),
            array('{', '}'),
            array('(', ')'),
            array('*', '*'),
            array('#', '#'),
            array('%', '%'),
            array('$', '$'),
        );

        return $fillBlanksAllowedSeparator;
    }

    /**
     * return the start separator for answer
     * @param string $number
     *
     * @return string
     */
    public static function getStartSeparator($number)
    {
        $listSeparators = self::getAllowedSeparator();

        return $listSeparators[$number][0];
    }

    /**
     * return the end separator for answer
     * @param string $number
     *
     * @return string
     */
    public static function getEndSeparator($number)
    {
        $listSeparators = self::getAllowedSeparator();

        return $listSeparators[$number][1];
    }

    /**
     * Return as a description text, array of allowed separators for question
     * eg: array("[...]", "(...)")
     * @return array
     */
    public static function getAllowedSeparatorForSelect()
    {
        $listResults = array();
        $fillBlanksAllowedSeparator = self::getAllowedSeparator();
        for ($i=0; $i < count($fillBlanksAllowedSeparator); $i++) {
            $listResults[] = $fillBlanksAllowedSeparator[$i][0]."...".$fillBlanksAllowedSeparator[$i][1];
        }

        return $listResults;
    }

    /**
     * return the code number of the separator for the question
     * @param string $startSeparator
     * @param string $endSeparator
     *
     * @return int
     */
    public function getDefaultSeparatorNumber($startSeparator, $endSeparator)
    {
        $listSeparators = self::getAllowedSeparator();
        $result = 0;
        for ($i=0; $i < count($listSeparators); $i++) {
            if ($listSeparators[$i][0] == $startSeparator &&
                $listSeparators[$i][1] == $endSeparator
            ) {
                $result = $i;
            }
        }

        return $result;
    }

    /**
     * return the HTML display of the answer
     * @param string $answer
     * @param bool   $resultsDisabled
     * @param bool $showTotalScoreAndUserChoices
     *
     * @return string
     */
    public static function getHtmlDisplayForAnswer($answer, $resultsDisabled = false, $showTotalScoreAndUserChoices = false)
    {
        $result = '';
        $listStudentAnswerInfo = self::getAnswerInfo($answer, true);

        if ($resultsDisabled == RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT) {
            if ($showTotalScoreAndUserChoices) {
                $resultsDisabled = true;
            } else {
                $resultsDisabled = false;
            }
        }

        // rebuild the answer with good HTML style
        // this is the student answer, right or wrong
        for ($i=0; $i < count($listStudentAnswerInfo['studentanswer']); $i++) {
            if ($listStudentAnswerInfo['studentscore'][$i] == 1) {
                $listStudentAnswerInfo['studentanswer'][$i] = self::getHtmlRightAnswer(
                    $listStudentAnswerInfo['studentanswer'][$i],
                    $listStudentAnswerInfo['tabwords'][$i],
                    $resultsDisabled
                );
            } else {
                $listStudentAnswerInfo['studentanswer'][$i] = self::getHtmlWrongAnswer(
                    $listStudentAnswerInfo['studentanswer'][$i],
                    $listStudentAnswerInfo['tabwords'][$i],
                    $resultsDisabled
                );
            }
        }


        // rebuild the sentence with student answer inserted
        for ($i=0; $i < count($listStudentAnswerInfo['commonwords']); $i++) {
            $result .= isset($listStudentAnswerInfo['commonwords'][$i]) ? $listStudentAnswerInfo['commonwords'][$i] : '';
            $result .= isset($listStudentAnswerInfo['studentanswer'][$i]) ? $listStudentAnswerInfo['studentanswer'][$i] : '';
        }

        // the last common word (should be </p>)
        $result .= isset($listStudentAnswerInfo['commonwords'][$i]) ? $listStudentAnswerInfo['commonwords'][$i] : '';

        return $result;
    }

    /**
     * return the HTML code of answer for correct and wrong answer
     * @param string $answer
     * @param string $correct
     * @param string $right
     * @param bool   $resultsDisabled
     *
     * @return string
     */
    public static function getHtmlAnswer($answer, $correct, $right, $resultsDisabled = false)
    {
        $style = "color: green";
        if (!$right) {
            $style = "color: red; text-decoration: line-through;";
        }
        $type = FillBlanks::getFillTheBlankAnswerType($correct);
        switch ($type) {
            case self::FILL_THE_BLANK_MENU:
                $correctAnswerHtml = '';
                $listPossibleAnswers = FillBlanks::getFillTheBlankMenuAnswers($correct, false);
                $correctAnswerHtml .= "<span style='color: green'>".$listPossibleAnswers[0]."</span>";
                $correctAnswerHtml .= " <span style='font-weight:normal'>(";
                for ($i=1; $i < count($listPossibleAnswers); $i++) {
                    $correctAnswerHtml .= $listPossibleAnswers[$i];
                    if ($i != count($listPossibleAnswers) - 1) {
                        $correctAnswerHtml .= " | ";
                    }
                }
                $correctAnswerHtml .= ")</span>";
                break;
            case self::FILL_THE_BLANK_SEVERAL_ANSWER:
                $listCorrects = explode("||", $correct);
                $firstCorrect = $correct;
                if (count($listCorrects) > 0) {
                    $firstCorrect = $listCorrects[0];
                }
                $correctAnswerHtml = "<span style='color: green'>".$firstCorrect."</span>";
                break;
            case self::FILL_THE_BLANK_STANDARD:
            default:
                $correctAnswerHtml = "<span style='color: green'>".$correct."</span>";
        }

        if ($resultsDisabled) {
            $correctAnswerHtml = "<span title='".get_lang("ExerciseWithFeedbackWithoutCorrectionComment")."'> - </span>";
        }

        $result = "<span style='border:1px solid black; border-radius:5px; padding:2px; font-weight:bold;'>";
        $result .= "<span style='$style'>".$answer."</span>";
        $result .= "&nbsp;<span style='font-size:120%;'>/</span>&nbsp;";
        $result .= $correctAnswerHtml;
        $result .= "</span>";

        return $result;
    }

    /**
     * return HTML code for correct answer
     * @param string $answer
     * @param string $correct
     * @param bool   $resultsDisabled
     *
     * @return string
     */
    public static function getHtmlRightAnswer($answer, $correct, $resultsDisabled = false)
    {
        return self::getHtmlAnswer($answer, $correct, true, $resultsDisabled);
    }

    /**
     * return HTML code for wrong answer
     * @param string $answer
     * @param string $correct
     * @param bool   $resultsDisabled
     *
     * @return string
     */
    public static function getHtmlWrongAnswer($answer, $correct, $resultsDisabled = false)
    {
        return self::getHtmlAnswer($answer, $correct, false, $resultsDisabled);
    }
}
