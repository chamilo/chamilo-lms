<?php

/* For licensing terms, see /license.txt */

/**
 *  Class FillBlanks.
 *
 * @author Eric Marguin
 * @author Julio Montoya multiple fill in blank option added.
 */
class FillBlanks extends Question
{
    public const FILL_THE_BLANK_STANDARD = 0;
    public const FILL_THE_BLANK_MENU = 1;
    public const FILL_THE_BLANK_SEVERAL_ANSWER = 2;

    public $typePicture = 'fill_in_blanks.png';
    public $explanationLangVar = 'FillBlanks';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = FILL_IN_BLANKS;
        $this->isContent = $this->getIsContent();
    }

    /**
     * {@inheritdoc}
     */
    public function createAnswersForm($form)
    {
        $defaults = [];
        $defaults['answer'] = get_lang('DefaultTextInBlanks');
        $defaults['select_separator'] = 0;
        $blankSeparatorNumber = 0;
        if (!empty($this->iid)) {
            $objectAnswer = new Answer($this->iid);
            $answer = $objectAnswer->selectAnswer(1);
            $listAnswersInfo = self::getAnswerInfo($answer);
            $defaults['multiple_answer'] = 0;
            if ($listAnswersInfo['switchable']) {
                $defaults['multiple_answer'] = 1;
            }
            // Take the complete string except after the last '::'
            $defaults['answer'] = $listAnswersInfo['text'];
            $defaults['select_separator'] = $listAnswersInfo['blank_separator_number'];
            $blankSeparatorNumber = $listAnswersInfo['blank_separator_number'];
        }

        $blankSeparatorStart = self::getStartSeparator($blankSeparatorNumber);
        $blankSeparatorEnd = self::getEndSeparator($blankSeparatorNumber);
        $setWeightAndSize = '';
        if (isset($listAnswersInfo) && count($listAnswersInfo['weighting']) > 0) {
            foreach ($listAnswersInfo['weighting'] as $i => $weighting) {
                $setWeightAndSize .= 'document.getElementById("weighting['.$i.']").value = "'.$weighting.'";';
            }
            foreach ($listAnswersInfo['input_size'] as $i => $sizeOfInput) {
                $setWeightAndSize .= 'document.getElementById("sizeofinput['.$i.']").value = "'.$sizeOfInput.'";';
                $setWeightAndSize .= 'document.getElementById("samplesize['.$i.']").style.width = "'.$sizeOfInput.'px";';
            }
        }

        $questionTypes = [FILL_IN_BLANKS => 'fillblanks', FILL_IN_BLANKS_COMBINATION => 'fillblanks_combination'];
        echo '<script>
            var questionType = "'.$questionTypes[$this->type].'";
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
                fields += "<label class=\"col-sm-2 control-label\"></label>";
                fields += "<div class=\"col-sm-8\">";
                fields += "<table class=\"data_table\">";
                fields += "<tr><th style=\"width:220px\">'.get_lang('WordTofind').'</th>";
                if (questionType == "fillblanks") {
                    fields += "<th style=\"width:50px\">'.get_lang('QuestionWeighting').'</th>";
                }
                fields += "<th>'.get_lang('BlankInputSize').'</th></tr>";

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
                        var blanksWithColor = trimBlanksBetweenSeparator(blanks[i], blankSeparatorStart, blankSeparatorEnd, 1);

                        fields += "<tr>";
                        fields += "<td>"+blanksWithColor+"</td>";
                        if (questionType == "fillblanks") {
                            fields += "<td><input class=\"form-control\" style=\"width:60px\" value=\""+value+"\" type=\"text\" id=\"weighting["+i+"]\" name=\"weighting["+i+"]\" /></td>";
                        } else {
                          fields += "<input value=\"0\" type=\"hidden\" id=\"weighting["+i+"]\" name=\"weighting["+i+"]\" />";
                        }

                        fields += "<td>";
                        fields += "<input class=\"btn btn-default\" type=\"button\" value=\"-\" onclick=\"changeInputSize(-1, "+i+")\">&nbsp;";
                        fields += "<input class=\"btn btn-default\" type=\"button\" value=\"+\" onclick=\"changeInputSize(1, "+i+")\">&nbsp;";
                        fields += "&nbsp;&nbsp;<input class=\"sample\" id=\"samplesize["+i+"]\" data-btoa=\""+btoaValue+"\"   type=\"text\" value=\""+textValue+"\" style=\"width:"+inputSize+"px\" disabled=disabled />";
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
                /* get current select blank type and replaced into #defineoneblank */
                var definedSeparator = $("[name=select_separator] option:selected").text();
                $("[name=select_separator] option").each(function (index, value) {
                    $("#defineoneblank").html($("#defineoneblank").html().replace($(value).html(), definedSeparator))
                });
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
            function getSeparatorFromNumber(number)
            {
                var separator = new Array();
                separator[0] = new Array("[", "]");
                separator[1] = new Array("{", "}");
                separator[2] = new Array("(", ")");
                separator[3] = new Array("*", "*");
                separator[4] = new Array("#", "#");
                separator[5] = new Array("%", "%");
                separator[6] = new Array("$", "$");
                return separator[number];
            }

            function trimBlanksBetweenSeparator(inTxt, inSeparatorStart, inSeparatorEnd, addColor)
            {
                var result = inTxt
                result = result.replace(inSeparatorStart, "");
                result = result.replace(inSeparatorEnd, "");
                result = result.trim();

                if (addColor == 1) {
                    var resultParts = result.split("|");
                    var partsToString = "";
                    resultParts.forEach(function(item, index) {
                        if (index == 0) {
                            item = "<b><font style=\"color:green\"> " + item +"</font></b>";
                        }
                        if (index < resultParts.length - 1) {
                            item  = item + " | ";
                        }
                        partsToString += item;
                    });
                    result = partsToString;
                }

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
            ['ToolbarSet' => 'TestQuestionDescription']
        );
        $form->addRule('answer', get_lang('GiveText'), 'required');

        //added multiple answers
        $form->addElement('checkbox', 'multiple_answer', '', get_lang('FillInBlankSwitchable'));
        $form->addElement(
            'select',
            'select_separator',
            get_lang('SelectFillTheBlankSeparator'),
            self::getAllowedSeparatorForSelect(),
            ' id="select_separator" style="width:150px" class="selectpicker" onchange="changeBlankSeparator()" '
        );
        $form->addLabel(
            null,
            '<input type="button" onclick="updateBlanks()" value="'.get_lang('RefreshBlanks').'" class="btn btn-default" />'
        );

        $form->addHtml('<div id="blanks_weighting"></div>');

        global $text;
        // setting the save button here and not in the question class.php
        $form->addHtml('<div id="defineoneblank" style="color:#D04A66; margin-left:160px">'.get_lang('DefineBlanks').'</div>');

        if (FILL_IN_BLANKS_COMBINATION === $this->type) {
            //only 1 answer the all deal ...
            $form->addText('questionWeighting', get_lang('Score'), true, ['value' => 10]);
            if (!empty($this->iid)) {
                $defaults['questionWeighting'] = $this->weighting;
            }
        }

        $form->addButtonSave($text, 'submitQuestion');

        if (!empty($this->iid)) {
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
            for ($i = 0; $i < $nb; $i++) {
                // enter the weighting of word $i
                $answer .= $form->getSubmitValue('weighting['.$i.']');
                // not the last word, add ","
                if ($i != $nb - 1) {
                    $answer .= ',';
                }
                // calculate the global weighting for the question
                $this->weighting += (float) $form->getSubmitValue('weighting['.$i.']');
            }

            if (FILL_IN_BLANKS_COMBINATION === $this->type) {
                $this->weighting = $form->getSubmitValue('questionWeighting');
            }

            // input width
            $answer .= ':';
            for ($i = 0; $i < $nb; $i++) {
                // enter the width of input for word $i
                $answer .= $form->getSubmitValue('sizeofinput['.$i.']');
                // not the last word, add ","
                if ($i != $nb - 1) {
                    $answer .= ',';
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
        $answer .= ':'.$form->getSubmitValue('select_separator');

        // Allow answers order switches
        $is_multiple = $form->getSubmitValue('multiple_answer');
        $answer .= '@'.$is_multiple;

        $this->save($exercise);
        $objAnswer = new Answer($this->iid);
        $objAnswer->createAnswer($answer, 0, '', 0, 1);
        $objAnswer->save();
    }

    /**
     * {@inheritdoc}
     */
    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $header = parent::return_header($exercise, $counter, $score);
        $header .= '<table class="'.$this->question_table_class.'">
            <tr>
                <th>'.get_lang('Answer').'</th>
            </tr>';

        return $header;
    }

    /**
     * @param int    $currentQuestion
     * @param int    $questionId
     * @param string $correctItem
     * @param array  $attributes
     * @param string $answer
     * @param array  $listAnswersInfo
     * @param bool   $displayForStudent
     * @param int    $inBlankNumber
     * @param string $labelId
     *
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
        $inBlankNumber,
        $labelId = ''
    ) {
        $inTabTeacherSolution = $listAnswersInfo['words'];
        $inTeacherSolution = $inTabTeacherSolution[$inBlankNumber];

        if (empty($labelId)) {
            $labelId = 'choice_id_'.$currentQuestion.'_'.$inBlankNumber;
        }

        switch (self::getFillTheBlankAnswerType($inTeacherSolution)) {
            case self::FILL_THE_BLANK_MENU:
                $selected = '';
                // the blank menu
                // display a menu from answer separated with |
                // if display for student, shuffle the correct answer menu
                $listMenu = self::getFillTheBlankMenuAnswers(
                    $inTeacherSolution,
                    $displayForStudent
                );

                $resultOptions = ['' => '--'];
                foreach ($listMenu as $item) {
                    $resultOptions[sha1($item)] = self::replaceSpecialCharsForMenuValues($item);
                }
                // It is checked special chars used in menu
                $correctItem = self::replaceSpecialCharsForMenuValues($correctItem);
                foreach ($resultOptions as $key => $value) {
                    if ($correctItem == $value) {
                        $selected = $key;

                        break;
                    }
                }
                $width = '';
                if (!empty($attributes['style'])) {
                    $width = str_replace('width:', '', $attributes['style']);
                }

                $result = Display::select(
                    "choice[$questionId][]",
                    $resultOptions,
                    $selected,
                    [
                        'class' => 'selectpicker',
                        'data-width' => $width,
                        'id' => $labelId,
                    ],
                    false
                );
                break;
            case self::FILL_THE_BLANK_SEVERAL_ANSWER:
            case self::FILL_THE_BLANK_STANDARD:
            default:
                $attributes['id'] = $labelId;
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

    /*
     * It searchs and replaces special chars to show in menu values
     *
     * @param string $value The value to parse
     *
     * @return string
     */
    public static function replaceSpecialCharsForMenuValues($value)
    {
        // It replaces supscript numbers
        $value = preg_replace('/<sup>([0-9]+)<\/sup>/is', "&sub$1;", $value);

        // It replaces subscript numbers
        $value = preg_replace_callback(
            "/<sub>([0-9]+)<\/sub>/is",
            function ($m) {
                $precode = '&#832';
                $nb = $m[1];
                $code = '';
                if (is_numeric($nb) && strlen($nb) > 1) {
                    for ($i = 0; $i < strlen($nb); $i++) {
                        $code .= $precode.$nb[$i].';';
                    }
                } else {
                    $code = $precode.$m[1].';';
                }

                return $code;
            },
            $value);

        return $value;
    }

    /**
     * Return an array with the different choices available
     * when the answers between bracket show as a menu.
     *
     * @param string $correctAnswer
     * @param bool   $displayForStudent true if we want to shuffle the choices of the menu for students
     *
     * @return array
     */
    public static function getFillTheBlankMenuAnswers($correctAnswer, $displayForStudent)
    {
        $list = api_preg_split("/\|/", $correctAnswer);
        foreach ($list as &$item) {
            $item = self::trimOption($item);
            $item = api_html_entity_decode($item);
        }
        // The list is always in the same order, there's no option to allow or disable shuffle options.
        if ($displayForStudent) {
            shuffle_assoc($list);
        }

        return $list;
    }

    /**
     * Return the array index of the student answer.
     *
     * @param string $correctAnswer the menu Choice1|Choice2|Choice3
     * @param string $studentAnswer the student answer must be Choice1 or Choice2 or Choice3
     *
     * @return int in the example 0 1 or 2 depending of the choice of the student
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
     * Return the possible answer if the answer between brackets is a multiple choice menu.
     *
     * @param string $correctAnswer
     *
     * @return array
     */
    public static function getFillTheBlankSeveralAnswers($correctAnswer)
    {
        // is answer||Answer||response||Response , mean answer or Answer ...
        return api_preg_split("/\|\|/", $correctAnswer);
    }

    /**
     * Return true if student answer is right according to the correctAnswer
     * it is not as simple as equality, because of the type of Fill The Blank question
     * eg : studentAnswer = 'Un' and correctAnswer = 'Un||1||un'.
     *
     * @param string $studentAnswer       [student_answer] of the info array of the answer field
     * @param string $correctAnswer       [words] of the info array of the answer field
     * @param bool   $fromDatabase        Optional
     * @param bool   $studentAnswerIsHash Optional.
     */
    public static function isStudentAnswerGood(
        string $studentAnswer,
        string $correctAnswer,
        bool $fromDatabase = false,
        bool $studentAnswerIsHash = false
    ): bool {
        $result = false;
        switch (self::getFillTheBlankAnswerType($correctAnswer)) {
            case self::FILL_THE_BLANK_MENU:
                $listMenu = self::getFillTheBlankMenuAnswers($correctAnswer, false);
                if ($studentAnswer != '' && isset($listMenu[0])) {
                    // First item is always the correct one.
                    $item = $listMenu[0];
                    if (!$fromDatabase) {
                        $item = sha1($item);

                        if (!$studentAnswerIsHash) {
                            $studentAnswer = sha1($studentAnswer);
                        }
                    }
                    if ($item === $studentAnswer) {
                        $result = true;
                    }
                }
                break;
            case self::FILL_THE_BLANK_SEVERAL_ANSWER:
                // the answer must be one of the choice made
                $listSeveral = self::getFillTheBlankSeveralAnswers($correctAnswer);
                $listSeveral = array_map(
                    function ($item) {
                        return self::trimOption(api_html_entity_decode($item));
                    },
                    $listSeveral
                );
                //$studentAnswer = htmlspecialchars($studentAnswer);
                $result = in_array($studentAnswer, $listSeveral);
                break;
            case self::FILL_THE_BLANK_STANDARD:
            default:
                $correctAnswer = api_html_entity_decode($correctAnswer);
                //$studentAnswer = htmlspecialchars($studentAnswer);
                $result = $studentAnswer == self::trimOption($correctAnswer);
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
        $type = self::FILL_THE_BLANK_STANDARD;
        if (api_strpos($correctAnswer, '|') && !api_strpos($correctAnswer, '||')) {
            $type = self::FILL_THE_BLANK_MENU;
        } elseif (api_strpos($correctAnswer, '||')) {
            $type = self::FILL_THE_BLANK_SEVERAL_ANSWER;
        }

        return $type;
    }

    /**
     * Return information about the answer.
     *
     * @param string $userAnswer      the text of the answer of the question
     * @param bool   $isStudentAnswer true if it's a student answer false the empty question model
     *
     * @return array of information about the answer
     */
    public static function getAnswerInfo($userAnswer = '', $isStudentAnswer = false)
    {
        $listAnswerResults = [];
        $listAnswerResults['text'] = '';
        $listAnswerResults['words_count'] = 0;
        $listAnswerResults['words_with_bracket'] = [];
        $listAnswerResults['words'] = [];
        $listAnswerResults['weighting'] = [];
        $listAnswerResults['input_size'] = [];
        $listAnswerResults['switchable'] = '';
        $listAnswerResults['student_answer'] = [];
        $listAnswerResults['student_score'] = [];
        $listAnswerResults['blank_separator_number'] = 0;
        $listDoubleColon = [];

        api_preg_match("/(.*)::(.*)$/s", $userAnswer, $listResult);

        if (count($listResult) < 2) {
            $listDoubleColon[] = '';
            $listDoubleColon[] = '';
        } else {
            $listDoubleColon[] = $listResult[1];
            $listDoubleColon[] = $listResult[2];
        }

        $listAnswerResults['system_string'] = $listDoubleColon[1];

        // Make sure we only take the last bit to find special marks
        $listArobaseSplit = explode('@', $listDoubleColon[1]);

        if (count($listArobaseSplit) < 2) {
            $listArobaseSplit[1] = '';
        }

        // Take the complete string except after the last '::'
        $listDetails = explode(':', $listArobaseSplit[0]);

        // < number of item after the ::[score]:[size]:[separator_id]@ , here there are 3
        if (count($listDetails) < 3) {
            $listWeightings = explode(',', $listDetails[0]);
            $listSizeOfInput = [];
            for ($i = 0; $i < count($listWeightings); $i++) {
                $listSizeOfInput[] = 200;
            }
            $blankSeparatorNumber = 0; // 0 is [...]
        } else {
            $listWeightings = explode(',', $listDetails[0]);
            $listSizeOfInput = explode(',', $listDetails[1]);
            $blankSeparatorNumber = $listDetails[2];
        }

        $listAnswerResults['text'] = $listDoubleColon[0];
        $listAnswerResults['weighting'] = $listWeightings;
        $listAnswerResults['input_size'] = $listSizeOfInput;
        $listAnswerResults['switchable'] = $listArobaseSplit[1];
        $listAnswerResults['blank_separator_start'] = self::getStartSeparator($blankSeparatorNumber);
        $listAnswerResults['blank_separator_end'] = self::getEndSeparator($blankSeparatorNumber);
        $listAnswerResults['blank_separator_number'] = $blankSeparatorNumber;

        $blankCharStart = self::getStartSeparator($blankSeparatorNumber);
        $blankCharEnd = self::getEndSeparator($blankSeparatorNumber);
        $blankCharStartForRegexp = self::escapeForRegexp($blankCharStart);
        $blankCharEndForRegexp = self::escapeForRegexp($blankCharEnd);

        // Get all blanks words
        $listAnswerResults['words_count'] = api_preg_match_all(
            '/'.$blankCharStartForRegexp.'[^'.$blankCharEndForRegexp.']*'.$blankCharEndForRegexp.'/',
            $listDoubleColon[0],
            $listWords
        );

        if ($listAnswerResults['words_count'] > 0) {
            $listAnswerResults['words_with_bracket'] = $listWords[0];
            // remove [ and ] in string
            array_walk(
                $listWords[0],
                function (&$value, $key, $tabBlankChar) {
                    $trimChars = '';
                    for ($i = 0; $i < count($tabBlankChar); $i++) {
                        $trimChars .= $tabBlankChar[$i];
                    }
                    $value = trim($value, $trimChars);
                },
                [$blankCharStart, $blankCharEnd]
            );
            $listAnswerResults['words'] = $listWords[0];
        }

        // Get all common words
        $commonWords = api_preg_replace(
            '/'.$blankCharStartForRegexp.'[^'.$blankCharEndForRegexp.']*'.$blankCharEndForRegexp.'/',
            '::',
            $listDoubleColon[0]
        );

        // if student answer, the second [] is the student answer,
        // the third is if student scored or not
        $listBrackets = [];
        $listWords = [];
        if ($isStudentAnswer) {
            for ($i = 0; $i < count($listAnswerResults['words']); $i++) {
                $listBrackets[] = $listAnswerResults['words_with_bracket'][$i];
                $listWords[] = $listAnswerResults['words'][$i];
                if ($i + 1 < count($listAnswerResults['words'])) {
                    // should always be
                    $i++;
                }
                $listAnswerResults['student_answer'][] = $listAnswerResults['words'][$i];
                if ($i + 1 < count($listAnswerResults['words'])) {
                    // should always be
                    $i++;
                }
                $listAnswerResults['student_score'][] = $listAnswerResults['words'][$i];
            }
            $listAnswerResults['words'] = $listWords;
            $listAnswerResults['words_with_bracket'] = $listBrackets;

            // if we are in student view, we've got 3 times :::::: for common words
            $commonWords = api_preg_replace("/::::::/", '::', $commonWords);
        }
        $listAnswerResults['common_words'] = explode('::', $commonWords);
        $listAnswerResults['words_types'] = array_map(
            function ($word): int {
                return FillBlanks::getFillTheBlankAnswerType($word);
            },
            $listAnswerResults['words']
        );

        return $listAnswerResults;
    }

    /**
     * Return an array of student state answers for fill the blank questions
     * for each students that answered the question
     * -2  : didn't answer
     * -1  : student answer is wrong
     *  0  : student answer is correct
     * >0  : fill the blank question with choice menu, is the index of the student answer (right answer index is 0).
     *
     * @param int $testId
     * @param int $questionId
     * @param $studentsIdList
     * @param string $startDate
     * @param string $endDate
     * @param bool   $useLastAnsweredAttempt
     *
     * @return array
     *               (
     *               [student_id] => Array
     *               (
     *               [first fill the blank for question] => -1
     *               [second fill the blank for question] => 2
     *               [third fill the blank for question] => -1
     *               )
     *               )
     */
    public static function getFillTheBlankResult(
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
            return [];
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
        $userResult = [];
        // foreach attempts for all students starting with his older attempt
        while ($data = Database::fetch_array($res)) {
            $answer = self::getAnswerInfo($data['answer'], true);

            // for each bracket to find in this question
            foreach ($answer['student_answer'] as $bracketNumber => $studentAnswer) {
                if ($answer['student_answer'][$bracketNumber] != '') {
                    // student has answered this bracket, cool
                    switch (self::getFillTheBlankAnswerType($answer['words'][$bracketNumber])) {
                        case self::FILL_THE_BLANK_MENU:
                            // get the indice of the choosen answer in the menu
                            // we know that the right answer is the first entry of the menu, ie 0
                            // (remember, menu entries are shuffled when taking the test)
                            $userResult[$data['user_id']][$bracketNumber] = self::getFillTheBlankMenuAnswerNum(
                                $answer['words'][$bracketNumber],
                                $answer['student_answer'][$bracketNumber]
                            );
                            break;
                        default:
                            if (self::isStudentAnswerGood(
                                $answer['student_answer'][$bracketNumber],
                                $answer['words'][$bracketNumber]
                            )
                            ) {
                                $userResult[$data['user_id']][$bracketNumber] = 0; //  right answer
                            } else {
                                $userResult[$data['user_id']][$bracketNumber] = -1; // wrong answer
                            }
                    }
                } else {
                    // student didn't answer this bracket
                    if ($useLastAnsweredAttempt) {
                        // if we take into account the last answered attempt
                        if (!isset($userResult[$data['user_id']][$bracketNumber])) {
                            $userResult[$data['user_id']][$bracketNumber] = -2; // not answered
                        }
                    } else {
                        // we take the last attempt, even if the student answer the question before
                        $userResult[$data['user_id']][$bracketNumber] = -2; // not answered
                    }
                }
            }
        }

        return $userResult;
    }

    /**
     * Return the number of student that give at leat an answer in the fill the blank test.
     *
     * @param array $resultList
     *
     * @return int
     */
    public static function getNbResultFillBlankAll($resultList)
    {
        $outRes = 0;
        // for each student in group
        foreach ($resultList as $list) {
            $found = false;
            // for each bracket, if student has at least one answer ( choice > -2) then he pass the question
            foreach ($list as $choice) {
                if ($choice > -2 && !$found) {
                    $outRes++;
                    $found = true;
                }
            }
        }

        return $outRes;
    }

    /**
     * Replace the occurrence of blank word with [correct answer][student answer][answer is correct].
     *
     * @param array $listWithStudentAnswer
     *
     * @return string
     */
    public static function getAnswerInStudentAttempt($listWithStudentAnswer)
    {
        $separatorStart = $listWithStudentAnswer['blank_separator_start'];
        $separatorEnd = $listWithStudentAnswer['blank_separator_end'];
        // lets rebuild the sentence with [correct answer][student answer][answer is correct]
        $result = '';
        for ($i = 0; $i < count($listWithStudentAnswer['common_words']) - 1; $i++) {
            $answerValue = null;
            if (isset($listWithStudentAnswer['student_answer'][$i])) {
                $answerValue = $listWithStudentAnswer['student_answer'][$i];
            }
            $scoreValue = null;
            if (isset($listWithStudentAnswer['student_score'][$i])) {
                $scoreValue = $listWithStudentAnswer['student_score'][$i];
            }

            $result .= $listWithStudentAnswer['common_words'][$i];
            $result .= $listWithStudentAnswer['words_with_bracket'][$i];
            $result .= $separatorStart.$answerValue.$separatorEnd;
            $result .= $separatorStart.$scoreValue.$separatorEnd;
        }
        $result .= $listWithStudentAnswer['common_words'][$i];
        $result .= '::';
        // add the system string
        $result .= $listWithStudentAnswer['system_string'];

        return $result;
    }

    /**
     * This function is the same than the js one above getBlankSeparatorRegexp.
     *
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
     * This function must be the same than the js one getSeparatorFromNumber above.
     *
     * @return array
     */
    public static function getAllowedSeparator()
    {
        return [
            ['[', ']'],
            ['{', '}'],
            ['(', ')'],
            ['*', '*'],
            ['#', '#'],
            ['%', '%'],
            ['$', '$'],
        ];
    }

    /**
     * return the start separator for answer.
     *
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
     * return the end separator for answer.
     *
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
     * eg: array("[...]", "(...)").
     *
     * @return array
     */
    public static function getAllowedSeparatorForSelect()
    {
        $listResults = [];
        $allowedSeparator = self::getAllowedSeparator();
        foreach ($allowedSeparator as $part) {
            $listResults[] = $part[0].'...'.$part[1];
        }

        return $listResults;
    }

    /**
     * return the HTML display of the answer.
     *
     * @param string $answer
     * @param int    $feedbackType
     * @param bool   $resultsDisabled
     * @param bool   $showTotalScoreAndUserChoices
     *
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

        // rebuild the answer with good HTML style
        // this is the student answer, right or wrong
        for ($i = 0; $i < count($listStudentAnswerInfo['student_answer']); $i++) {
            if ($listStudentAnswerInfo['student_score'][$i] == 1) {
                $listStudentAnswerInfo['student_answer'][$i] = self::getHtmlRightAnswer(
                    $listStudentAnswerInfo['student_answer'][$i],
                    $listStudentAnswerInfo['words'][$i],
                    $feedbackType,
                    $resultsDisabled,
                    $showTotalScoreAndUserChoices
                );
            } else {
                $listStudentAnswerInfo['student_answer'][$i] = self::getHtmlWrongAnswer(
                    $listStudentAnswerInfo['student_answer'][$i],
                    $listStudentAnswerInfo['words'][$i],
                    $feedbackType,
                    $resultsDisabled,
                    $showTotalScoreAndUserChoices
                );
            }
        }

        // rebuild the sentence with student answer inserted
        for ($i = 0; $i < count($listStudentAnswerInfo['common_words']); $i++) {
            if ($resultsDisabled == RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK) {
                if (empty($listStudentAnswerInfo['student_answer'][$i])) {
                    continue;
                }
            }
            $result .= isset($listStudentAnswerInfo['common_words'][$i]) ? $listStudentAnswerInfo['common_words'][$i] : '';
            $studentLabel = isset($listStudentAnswerInfo['student_answer'][$i]) ? $listStudentAnswerInfo['student_answer'][$i] : '';
            $result .= $studentLabel;
        }

        // the last common word (should be </p>)
        $result .= isset($listStudentAnswerInfo['common_words'][$i]) ? $listStudentAnswerInfo['common_words'][$i] : '';

        return $result;
    }

    /**
     * return the HTML code of answer for correct and wrong answer.
     *
     * @param string $answer
     * @param string $correct
     * @param string $right
     * @param int    $feedbackType
     * @param bool   $resultsDisabled
     * @param bool   $showTotalScoreAndUserChoices
     *
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
        $hideUserSelection = false;
        switch ($resultsDisabled) {
            case RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING:
            case RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER:
                $hideUserSelection = true;
                break;
            case RESULT_DISABLE_SHOW_SCORE_ONLY:
                if (0 == $feedbackType) {
                    $hideExpectedAnswer = true;
                }
                break;
            case RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK:
            case RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK:
            case RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT:
                $hideExpectedAnswer = true;
                if ($showTotalScoreAndUserChoices) {
                    $hideExpectedAnswer = false;
                }
                break;
        }

        $style = 'feedback-green';
        $iconAnswer = Display::return_icon('attempt-check.png', get_lang('Correct'), null, ICON_SIZE_SMALL);
        if (!$right) {
            $style = 'feedback-red';
            $iconAnswer = Display::return_icon('attempt-nocheck.png', get_lang('Incorrect'), null, ICON_SIZE_SMALL);
        }

        $correctAnswerHtml = '';
        $type = self::getFillTheBlankAnswerType($correct);
        switch ($type) {
            case self::FILL_THE_BLANK_MENU:
                $listPossibleAnswers = self::getFillTheBlankMenuAnswers($correct, false);
                $correctAnswerHtml .= "<span class='correct-answer'><strong>".$listPossibleAnswers[0]."</strong>";
                $correctAnswerHtml .= ' (';
                for ($i = 1; $i < count($listPossibleAnswers); $i++) {
                    $correctAnswerHtml .= $listPossibleAnswers[$i];
                    if ($i != count($listPossibleAnswers) - 1) {
                        $correctAnswerHtml .= ' | ';
                    }
                }
                $correctAnswerHtml .= ")</span>";
                break;
            case self::FILL_THE_BLANK_SEVERAL_ANSWER:
                $listCorrects = explode('||', $correct);
                $firstCorrect = $correct;
                if (count($listCorrects) > 0) {
                    $firstCorrect = $listCorrects[0];
                }
                $correctAnswerHtml = "<span class='correct-answer'>".$firstCorrect."</span>";
                break;
            case self::FILL_THE_BLANK_STANDARD:
            default:
                $correctAnswerHtml = "<span class='correct-answer'>".$correct."</span>";
        }

        if ($hideExpectedAnswer) {
            $correctAnswerHtml = "<span
                class='feedback-green'
                title='".get_lang('ExerciseWithFeedbackWithoutCorrectionComment')."'> &#8212; </span>";
        }

        $result = "<span class='feedback-question'>";
        if ($hideUserSelection === false) {
            $result .= $iconAnswer."<span class='$style'>".$answer."</span>";
        }
        $result .= "<span class='feedback-separator'>|</span>";
        $result .= $correctAnswerHtml;
        $result .= '</span>';

        return $result;
    }

    /**
     * return HTML code for correct answer.
     *
     * @param string $answer
     * @param string $correct
     * @param string $feedbackType
     * @param bool   $resultsDisabled
     * @param bool   $showTotalScoreAndUserChoices
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
     * return HTML code for wrong answer.
     *
     * @param string $answer
     * @param string $correct
     * @param string $feedbackType
     * @param bool   $resultsDisabled
     * @param bool   $showTotalScoreAndUserChoices
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
     * Check if a answer is correct by its text.
     *
     * @param string $answerText
     *
     * @return bool
     */
    public static function isCorrect($answerText)
    {
        $answerInfo = self::getAnswerInfo($answerText, true);
        $correctAnswerList = $answerInfo['words'];
        $studentAnswer = $answerInfo['student_answer'];
        $isCorrect = true;

        foreach ($correctAnswerList as $i => $correctAnswer) {
            $value = self::isStudentAnswerGood($studentAnswer[$i], $correctAnswer);
            $isCorrect = $isCorrect && $value;
        }

        return $isCorrect;
    }

    /**
     * Clear the answer entered by student.
     *
     * @param string $answer
     *
     * @return string
     */
    public static function clearStudentAnswer($answer)
    {
        $answer = htmlentities(api_utf8_encode($answer), ENT_QUOTES);
        $answer = str_replace('&#039;', '&#39;', $answer); // fix apostrophe
        $answer = api_preg_replace('/\s\s+/', ' ', $answer); // replace excess white spaces
        $answer = strtr($answer, array_flip(get_html_translation_table(HTML_ENTITIES, ENT_QUOTES)));

        return trim($answer);
    }

    /**
     * Removes double spaces between words.
     *
     * @param string $text
     *
     * @return string
     */
    private static function trimOption($text)
    {
        $text = trim($text);

        return preg_replace("/\s+/", ' ', $text);
    }
}
