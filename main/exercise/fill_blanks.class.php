<?php
/* For licensing terms, see /license.txt */

/**
 *  Class FillBlanks
 *
 * @author Eric Marguin
 * @author Julio Montoya multiple fill in blank option added
 * @package chamilo.exercise
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
     * @inheritdoc
     */
    public function createAnswersForm($form)
    {
        $defaults = array();
        if (!empty($this->id)) {
            $objectAnswer = new Answer($this->id);
            $answer = $objectAnswer->selectAnswer(1);
            $listAnswersInfo = self::getAnswerInfo($answer);
            if ($listAnswersInfo['switchable']) {
                $defaults['multiple_answer'] = 1;
            } else {
                $defaults['multiple_answer'] = 0;
            }
            //take the complete string except after the last '::'
            $defaults['answer'] = $listAnswersInfo['text'];
            $defaults['select_separator'] = $listAnswersInfo['blankseparatornumber'];
            $blankSeparatorNumber = $listAnswersInfo['blankseparatornumber'];
        } else {
            $defaults['answer'] = get_lang('DefaultTextInBlanks');
            $defaults['select_separator'] = 0;
            $blankSeparatorNumber = 0;
        }

        $blankSeparatorStart = self::getStartSeparator($blankSeparatorNumber);
        $blankSeparatorEnd = self::getEndSeparator($blankSeparatorNumber);

        $setWeightAndSize = '';
        if (isset($listAnswersInfo) && count($listAnswersInfo['tabweighting']) > 0) {
            foreach ($listAnswersInfo['tabweighting'] as $i => $weighting) {
                $setWeightAndSize .= 'document.getElementById("weighting['.$i.']").value = "'.$weighting.'";';
            }
            foreach ($listAnswersInfo['tabinputsize'] as $i => $sizeOfInput) {
                $setWeightAndSize .= 'document.getElementById("sizeofinput['.$i.']").value = "'.$sizeOfInput.'";';
                $setWeightAndSize .= 'document.getElementById("samplesize['.$i.']").style.width = "'.$sizeOfInput.'px";';
            }
        }

        echo '<script>
            
            var firstTime = true;            
            var originalOrder = new Array();   
            var blankSeparatorStart = "'.$blankSeparatorStart.'";
            var blankSeparatorEnd = "'.$blankSeparatorEnd.'";
            var blankSeparatorStartRegexp = getBlankSeparatorRegexp(blankSeparatorStart);
            var blankSeparatorEndRegexp = getBlankSeparatorRegexp(blankSeparatorEnd);
            var blanksRegexp = "/"+blankSeparatorStartRegexp+"[^"+blankSeparatorStartRegexp+"]*"+blankSeparatorEndRegexp+"/g";
            
            CKEDITOR.on("instanceCreated", function(e) {
                if (e.editor.name === "answer") {                  
                    //e.editor.on("change", updateBlanks);
                    e.editor.on("change", function(){
                        updateBlanks();
                    });
                }
            });                        
            
            function updateBlanks()
            {                
                var answer;                
                if (firstTime) {
                    var field = document.getElementById("answer");
                    answer = field.value;
                } else {
                    answer = CKEDITOR.instances["answer"].getData();
                }
                                
                // disable the save button, if not blanks have been created
                $("button").attr("disabled", "disabled");
                $("#defineoneblank").show();                
                var blanks = answer.match(eval(blanksRegexp));             
                var fields = "<div class=\"form-group \">";                
                fields += "<label class=\"col-sm-2 control-label\">'.get_lang('Weighting').'</label>";
                fields += "<div class=\"col-sm-8\">";
                fields += "<table>";
                fields += "<tr><th style=\"padding:0 20px\">'.get_lang("WordTofind").'</th><th style=\"padding:0 20px\">'.get_lang("QuestionWeighting").'</th><th style=\"padding:0 20px\">'.get_lang("BlankInputSize").'</th></tr>";

                if (blanks != null) {
                    for (var i=0; i < blanks.length; i++) {
                        // remove forbidden characters that causes bugs
                        blanks[i] = removeForbiddenChars(blanks[i]);                        
                        // trim blanks between brackets
                        blanks[i] = trimBlanksBetweenSeparator(blanks[i], blankSeparatorStart, blankSeparatorEnd);
                        
                        // if the word is empty []
                        if (blanks[i] == blankSeparatorStartRegexp+blankSeparatorEndRegexp) {
                            break;
                        }
                        
                        // get input size
                        var inputSize = 100;                        
                        var textValue = blanks[i].substr(1, blanks[i].length - 2);
                        var btoaValue = textValue.hashCode();
                                                                      
                        if (firstTime == false) {
                            var element = document.getElementById("samplesize["+i+"]");                                
                            if (element) {
                                inputSize = document.getElementById("sizeofinput["+i+"]").value;                                
                            }
                        }                                                                    

                        if (document.getElementById("weighting["+i+"]")) {
                            var value = document.getElementById("weighting["+i+"]").value;
                        } else {
                            var value = "1";    
                        }                                            
                        
                        fields += "<tr>";
                        fields += "<td>"+blanks[i]+"</td>";
                        fields += "<td><input style=\"width:35px\" value=\""+value+"\" type=\"text\" id=\"weighting["+i+"]\" name=\"weighting["+i+"]\" /></td>";
                        fields += "<td>";
                        fields += "<input class=\"btn btn-default\" type=\"button\" value=\"-\" onclick=\"changeInputSize(-1, "+i+")\">&nbsp;";
                        fields += "<input class=\"btn btn-default\" type=\"button\" value=\"+\" onclick=\"changeInputSize(1, "+i+")\">&nbsp;";
                        fields += "<input class=\"sample\" id=\"samplesize["+i+"]\" data-btoa=\""+btoaValue+"\"   type=\"text\" value=\""+textValue+"\" style=\"width:"+inputSize+"px\" disabled=disabled />";
                        fields += "<input id=\"sizeofinput["+i+"]\" type=\"hidden\" value=\""+inputSize+"\" name=\"sizeofinput["+i+"]\"  />";
                        fields += "</td>";
                        fields += "</tr>";
                        
                        // enable the save button
                        $("button").removeAttr("disabled");
                        $("#defineoneblank").hide();
                    }
                }                         
                
                document.getElementById("blanks_weighting").innerHTML = fields + "</table></div></div>";                                
                $(originalOrder).each(function(i, data) {
                     if (firstTime == false) {
                        value = data.value;                        
                        var d = $("input.sample[data-btoa=\'"+value+"\']");                        
                        var id = d.attr("id");   
                        if (id) {
                            var sizeInputId = id.replace("samplesize", "sizeofinput");                            
                            var sizeInputId = sizeInputId.replace("[", "\\\[");
                            var sizeInputId = sizeInputId.replace("]", "\\\]");                       
                                                         
                            $("#"+sizeInputId).val(data.width);                        
                            d.outerWidth(data.width+"px");
                        }
                    }
                });
                
                updateOrder(blanks);               

                if (firstTime) {
                    firstTime = false;
                    '.$setWeightAndSize.'
                }
            }

            window.onload = updateBlanks;
            
            String.prototype.hashCode = function() {
                var hash = 0, i, chr, len;
                if (this.length === 0) return hash;
                for (i = 0, len = this.length; i < len; i++) {
                    chr   = this.charCodeAt(i);
                    hash  = ((hash << 5) - hash) + chr;
                    hash |= 0; // Convert to 32bit integer
                }
                return hash;
            };
            
            function updateOrder(blanks) 
            {
                originalOrder = new Array();                
                 if (blanks != null) {
                    for (var i=0; i < blanks.length; i++) {
                        // remove forbidden characters that causes bugs
                        blanks[i] = removeForbiddenChars(blanks[i]);                        
                        // trim blanks between brackets
                        blanks[i] = trimBlanksBetweenSeparator(blanks[i], blankSeparatorStart, blankSeparatorEnd);
                        
                        // if the word is empty []
                        if (blanks[i] == blankSeparatorStartRegexp+blankSeparatorEndRegexp) {
                            break;
                        }                        
                        var textValue = blanks[i].substr(1, blanks[i].length - 2);
                        var btoaValue = textValue.hashCode();
                        
                        if (firstTime == false) {
                            var element = document.getElementById("samplesize["+i+"]");                                
                            if (element) {
                                inputSize = document.getElementById("sizeofinput["+i+"]").value;
                                originalOrder.push({ "width" : inputSize, "value": btoaValue });                                                                               
                            }
                        }
                    }
                }
            }
            
            function changeInputSize(coef, inIdNum)
            {
                if (firstTime) {
                    var field = document.getElementById("answer");
                    answer = field.value;
                } else {
                    answer = CKEDITOR.instances["answer"].getData();
                }
                
                var blanks = answer.match(eval(blanksRegexp));
                var currentWidth = $("#samplesize\\\["+inIdNum+"\\\]").width();
                var newWidth = currentWidth + coef * 20;
                newWidth = Math.max(20, newWidth);
                newWidth = Math.min(newWidth, 600);
                $("#samplesize\\\["+inIdNum+"\\\]").outerWidth(newWidth);
                $("#sizeofinput\\\["+inIdNum+"\\\]").attr("value", newWidth);
                
                updateOrder(blanks); 
            }

            function removeForbiddenChars(inTxt)
            {
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
                blankSeparatorStart = tabSeparator[0];
                blankSeparatorEnd = tabSeparator[1];
                blankSeparatorStartRegexp = getBlankSeparatorRegexp(blankSeparatorStart);
                blankSeparatorEndRegexp = getBlankSeparatorRegexp(blankSeparatorEnd);
                blanksRegexp = "/"+blankSeparatorStartRegexp+"[^"+blankSeparatorStartRegexp+"]*"+blankSeparatorEndRegexp+"/g";
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
                var result = inTxt
                result = result.replace(inSeparatorStart, "");
                result = result.replace(inSeparatorEnd, "");
                result = result.trim();
                return inSeparatorStart+result+inSeparatorEnd;
            }
        </script>';

        // answer
        $form->addLabel(
            null,
            get_lang('TypeTextBelow').', '.get_lang('And').' '.get_lang('UseTagForBlank')
        );
        $form->addElement(
            'html_editor',
            'answer',
            Display::return_icon('fill_field.png'),
            ['id' => 'answer'],
            array('ToolbarSet' => 'TestQuestionDescription')
        );
        $form->addRule('answer', get_lang('GiveText'), 'required');

        //added multiple answers
        $form->addElement('checkbox', 'multiple_answer', '', get_lang('FillInBlankSwitchable'));
        $form->addElement(
            'select',
            'select_separator',
            get_lang("SelectFillTheBlankSeparator"),
            self::getAllowedSeparatorForSelect(),
            ' id="select_separator" style="width:150px" onchange="changeBlankSeparator()" '
        );
        $form->addLabel(
            null,
            '<input type="button" onclick="updateBlanks()" value="'.get_lang('RefreshBlanks').'" class="btn btn-default" />'
        );
        $form->addHtml('<div id="blanks_weighting"></div>');

        global $text;
        // setting the save button here and not in the question class.php
        $form->addHtml('<div id="defineoneblank" style="color:#D04A66; margin-left:160px">'.get_lang('DefineBlanks').'</div>');
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

        // remove the "::" eventually written by the user
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
            for ($i = 0; $i < $nb; ++$i) {
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
     * @param int $currentQuestion
     * @param int $questionId
     * @param string $correctItem
     * @param array $attributes
     * @param string $answer
     * @param array $listAnswersInfo
     * @param boolean $displayForStudent
     * @param int $inBlankNumber
     * @return string
     */
    public static function getFillTheBlankHtml(
        $currentQuestion,
        $questionId,
        $correctItem,
        $attributes,
        $answer,
        $listAnswersInfo,
        $displayForStudent,
        $inBlankNumber
    ) {
        $result = '';
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
                $result .= '<select id="choice_id_'.$currentQuestion.'_'.$inBlankNumber.'" name="choice['.$questionId.'][]">';
                for ($k=0; $k < count($listMenu); $k++) {
                    $selected = '';
                    if ($correctItem == $listMenu[$k]) {
                        $selected = " selected=selected ";
                    }
                    // if in teacher view, display the first item by default, which is the right answer
                    if ($k==0 && !$displayForStudent) {
                        $selected = " selected=selected ";
                    }
                    $optionMenu .= '<option '.$selected.' value="'.$listMenu[$k].'">'.$listMenu[$k].'</option>';
                }
                if ($selected == '') {
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
                $attributes['id'] = 'choice_id_'.$currentQuestion.'_'.$inBlankNumber;
                $result = Display::input(
                    'text',
                    "choice[$questionId][]",
                    $correctItem,
                    $attributes
                );
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
        $list = api_preg_split("/\|/", $correctAnswer);
        if ($displayForStudent) {
            shuffle($list);
        }

        return $list;
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
        $listAnswerResults['text'] = '';
        $listAnswerResults['wordsCount'] = 0;
        $listAnswerResults['tabwordsbracket'] = array();
        $listAnswerResults['tabwords'] = array();
        $listAnswerResults['tabweighting'] = array();
        $listAnswerResults['tabinputsize'] = array();
        $listAnswerResults['switchable'] = '';
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
            $listArobaseSplit[1] = '';
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
                    $trimChars = '';
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
    * @param bool $useLastAnsweredAttempt
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
        $useLastAnsweredAttempt = true
    ) {
        $tblTrackEAttempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $tblTrackEExercise = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $courseId = api_get_course_int_id();
        // If no user has answered questions, no need to go further. Return empty array.
        if (empty($studentsIdList)) {
            return array();
        }
        // request to have all the answers of student for this question
        // student may have doing it several time
        // student may have not answered the bracket id, in this case, is result of the answer is empty
        // we got the less recent attempt first
        $sql = 'SELECT * FROM '.$tblTrackEAttempt.' tea
                LEFT JOIN '.$tblTrackEExercise.' tee
                ON 
                    tee.exe_id = tea.exe_id AND 
                    tea.c_id = '.$courseId.' AND 
                    exe_exo_id = '.$testId.'    
               WHERE 
                    tee.c_id = '.$courseId.' AND 
                    question_id = '.$questionId.' AND 
                    tea.user_id IN ('.implode(',', $studentsIdList).')  AND 
                    tea.tms >= "'.$startDate.'" AND 
                    tea.tms <= "'.$endDate.'"
               ORDER BY user_id, tea.exe_id;
        ';

        $res = Database::query($sql);
        $tabUserResult = array();
        // foreach attempts for all students starting with his older attempt
        while ($data = Database::fetch_array($res)) {
            $tabAnswer = self::getAnswerInfo($data['answer'], true);

            // for each bracket to find in this question
            foreach ($tabAnswer['studentanswer'] as $bracketNumber => $studentAnswer) {
                if ($tabAnswer['studentanswer'][$bracketNumber] != '') {
                    // student has answered this bracket, cool
                    switch (self::getFillTheBlankAnswerType($tabAnswer['tabwords'][$bracketNumber])) {
                        case self::FILL_THE_BLANK_MENU:
                            // get the indice of the choosen answer in the menu
                            // we know that the right answer is the first entry of the menu, ie 0
                            // (remember, menu entries are shuffled when taking the test)
                            $tabUserResult[$data['user_id']][$bracketNumber] = self::getFillTheBlankMenuAnswerNum(
                                $tabAnswer['tabwords'][$bracketNumber],
                                $tabAnswer['studentanswer'][$bracketNumber]
                            );
                            break;
                        default:
                            if (self::isGoodStudentAnswer(
                                $tabAnswer['studentanswer'][$bracketNumber],
                                $tabAnswer['tabwords'][$bracketNumber]
                            )
                            ) {
                                $tabUserResult[$data['user_id']][$bracketNumber] = 0;   //  right answer
                            } else {
                                $tabUserResult[$data['user_id']][$bracketNumber] = -1;  // wrong answer
                            }
                    }
                } else {
                    // student didn't answer this bracket
                    if ($useLastAnsweredAttempt) {
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
     * @param array $resultList
     * @return int
     */
    public static function getNbResultFillBlankAll($resultList)
    {
        $outRes = 0;
        // for each student in group
        foreach ($resultList as $userId => $tabValue) {
            $found = false;
            // for each bracket, if student has at least one answer ( choice > -2) then he pass the question
            foreach ($tabValue as $i => $choice) {
                if ($choice > -2 && !$found) {
                    $outRes++;
                    $found = true;
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
        $result = '';
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
     * @param int $feedbackType
     * @param bool $resultsDisabled
     * @param bool $showTotalScoreAndUserChoices
     * @return string
     */
    public static function getHtmlDisplayForAnswer(
        $answer,
        $feedbackType,
        $resultsDisabled = false,
        $showTotalScoreAndUserChoices = false
    ) {
        $result = '';
        $listStudentAnswerInfo = self::getAnswerInfo($answer, true);

        if ($resultsDisabled == RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT) {
            if ($showTotalScoreAndUserChoices) {
                $resultsDisabled = false;
            } else {
                $resultsDisabled = true;
            }
        }

        // rebuild the answer with good HTML style
        // this is the student answer, right or wrong
        for ($i=0; $i < count($listStudentAnswerInfo['studentanswer']); $i++) {
            if ($listStudentAnswerInfo['studentscore'][$i] == 1) {
                $listStudentAnswerInfo['studentanswer'][$i] = self::getHtmlRightAnswer(
                    $listStudentAnswerInfo['studentanswer'][$i],
                    $listStudentAnswerInfo['tabwords'][$i],
                    $feedbackType,
                    $resultsDisabled,
                    $showTotalScoreAndUserChoices
                );
            } else {
                $listStudentAnswerInfo['studentanswer'][$i] = self::getHtmlWrongAnswer(
                    $listStudentAnswerInfo['studentanswer'][$i],
                    $listStudentAnswerInfo['tabwords'][$i],
                    $feedbackType,
                    $resultsDisabled,
                    $showTotalScoreAndUserChoices
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
     * @param int $feedbackType
     * @param bool $resultsDisabled
     * @param bool $showTotalScoreAndUserChoices
     * @return string
     */
    public static function getHtmlAnswer(
        $answer,
        $correct,
        $right,
        $feedbackType,
        $resultsDisabled = false,
        $showTotalScoreAndUserChoices = false
    ) {
        $hideExpectedAnswer = false;
        if ($feedbackType == 0 && ($resultsDisabled == RESULT_DISABLE_SHOW_SCORE_ONLY)) {
            $hideExpectedAnswer = true;
        }

        if ($resultsDisabled == RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT) {
            if ($showTotalScoreAndUserChoices) {
                $hideExpectedAnswer = false;
            } else {
                $hideExpectedAnswer = true;
            }
        }

        $style = "color: green";
        if (!$right) {
            $style = "color: red; text-decoration: line-through;";
        }
        $type = self::getFillTheBlankAnswerType($correct);
        switch ($type) {
            case self::FILL_THE_BLANK_MENU:
                $correctAnswerHtml = '';
                $listPossibleAnswers = self::getFillTheBlankMenuAnswers($correct, false);
                $correctAnswerHtml .= "<span style='color: green'>".$listPossibleAnswers[0]."</span>";
                $correctAnswerHtml .= " <span style='font-weight:normal'>(";
                for ($i = 1; $i < count($listPossibleAnswers); $i++) {
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

        if ($hideExpectedAnswer) {
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
    public static function getHtmlRightAnswer(
        $answer,
        $correct,
        $feedbackType,
        $resultsDisabled = false,
        $showTotalScoreAndUserChoices = false
    ) {
        return self::getHtmlAnswer(
            $answer,
            $correct,
            true,
            $feedbackType,
            $resultsDisabled,
            $showTotalScoreAndUserChoices
        );
    }

    /**
     * return HTML code for wrong answer
     * @param string $answer
     * @param string $correct
     * @param bool   $resultsDisabled
     *
     * @return string
     */
    public static function getHtmlWrongAnswer(
        $answer,
        $correct,
        $feedbackType,
        $resultsDisabled = false,
        $showTotalScoreAndUserChoices = false
    ) {
        return self::getHtmlAnswer(
            $answer,
            $correct,
            false,
            $feedbackType,
            $resultsDisabled,
            $showTotalScoreAndUserChoices
        );
    }

    /**
     * Check if a answer is correct by its text
     * @param string $answerText
     * @return bool
     */
    public static function isCorrect($answerText)
    {
        $answerInfo = self::getAnswerInfo($answerText, true);
        $correctAnswerList = $answerInfo['tabwords'];
        $studentAnswer = $answerInfo['studentanswer'];
        $isCorrect = true;

        foreach ($correctAnswerList as $i => $correctAnswer) {
            $isGoodStudentAnswer = self::isGoodStudentAnswer($studentAnswer[$i], $correctAnswer);
            $isCorrect = $isCorrect && $isGoodStudentAnswer;
        }

        return $isCorrect;
    }
}
