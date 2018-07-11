<?php

    /* For licensing terms, see /license.txt */

    /**
     * Class MultipleAnswerTrueFalseDegreeCertainty
     * This class allows to instantiate an object of type MULTIPLE_ANSWER
     * (MULTIPLE CHOICE, MULTIPLE ANSWER), extending the class question
     *
     * @package chamilo.exercise
     */
    class MultipleAnswerTrueFalseDegreeCertainty extends Question {

        static $typePicture = 'mccert.png';
        static $explanationLangVar = 'MultipleAnswerTrueFalseDegreeCertainty';
        public $optionsTitle;
        public $options;

        const LEVEL_DARKGREEN = 1;
        const LEVEL_LIGHTGREEN = 2;
        const LEVEL_WHITE = 3;
        const LEVEL_LIGHTRED = 4;
        const LEVEL_DARKRED = 5;

        // const TEST = get_lang('new');

        /**
         * Constructor
         */
        public function __construct() {
            parent::__construct();
            $this->type = MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY;
            $this->isContent = $this->getIsContent();
            $this->optionsTitle = [1 => 'langAnswers', 2 => 'DegreeOfCertainty'];
            $this->options = [
                1 => 'True',
                2 => 'False',
                3 => '50%',
                4 => '60%',
                5 => '70%',
                6 => '80%',
                7 => '90%',
                8 => '100%'
            ];
        }

        /**
         * function which redefines Question::createAnswersForm
         * @param FormValidator $form
         */
        public function createAnswersForm($form) {
            $nbAnswers = isset($_POST['nb_answers']) ? $_POST['nb_answers'] : 4;
            // The previous default value was 2. See task #1759.
            $nbAnswers += (isset($_POST['lessAnswers']) ? -1 : (isset($_POST['moreAnswers']) ? 1 : 0));

            $courseId = api_get_course_int_id();
            $objEx = $_SESSION['objExercise'];
            $renderer = & $form->defaultRenderer();
            $defaults = [];

            $html = '<table class="data_table"><tr style="text-align: center;"><th>'
                . get_lang('Number')
                . '</th><th>'
                . get_lang('True')
                . '</th><th>'
                . get_lang('False')
                . '</th><th>' 
                . get_lang('Answer')
                . '</th>';

            // show column comment when feedback is enable
            if ($objEx->selectFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM) {
                $html .='<th>' . get_lang('Comment') . '</th>';
            }
            $html .= '</tr>';
            $form->addElement('label', get_lang('Answers') . '<br /> <img src="../img/fill_field.png">', $html);

            $correct = 0;
            $answer = null;
            if (!empty($this->id)) {
                $answer = new Answer($this->id);
                $answer->read();
                if (count($answer->nbrAnswers) > 0 && !$form->isSubmitted()) {
                    $nbAnswers = $answer->nbrAnswers;
                }
            }

            $form->addElement('hidden', 'nb_answers');
            $boxesNames = [];

            if ($nbAnswers < 1) {
                $nbAnswers = 1;
                Display::display_normal_message(get_lang('YouHaveToCreateAtLeastOneAnswer'));
            }

            // Can be more options
            $optionData = Question::readQuestionOption($this->id, $courseId);


            for ($i = 1; $i <= $nbAnswers; ++$i) {

                $renderer->setElementTemplate(
                        '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                        'correct[' . $i . ']'
                );
                $renderer->setElementTemplate(
                        '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                        'counter[' . $i . ']'
                );
                $renderer->setElementTemplate(
                        '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                        'answer[' . $i . ']'
                );
                $renderer->setElementTemplate(
                        '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                        'comment[' . $i . ']'
                );

                $answerNumber = $form->addElement('text', 'counter[' . $i . ']', null, 'value="' . $i . '"');
                $answerNumber->freeze();

                if (is_object($answer)) {
                    $defaults['answer[' . $i . ']'] = $answer->answer[$i];
                    $defaults['comment[' . $i . ']'] = $answer->comment[$i];
                    $defaults['weighting[' . $i . ']'] = float_format($answer->weighting[$i], 1);

                    $correct = $answer->correct[$i];
                    $defaults['correct[' . $i . ']'] = $correct;

                    $j = 1;
                    if (!empty($optionData)) {
                        foreach ($optionData as $id => $data) {
                            $form->addElement('radio', 'correct[' . $i . ']', null, null, $id);
                            $j++;
                            if ($j == 3) {
                                break;
                            }
                        }
                    }
                } else {
                    $form->addElement('radio', 'correct[' . $i . ']', null, null, 1);
                    $form->addElement('radio', 'correct[' . $i . ']', null, null, 2);

                    $defaults['answer[' . $i . ']'] = '';
                    $defaults['comment[' . $i . ']'] = '';
                    $defaults['correct[' . $i . ']'] = '';
                }

                $boxesNames[] = 'correct[' . $i . ']';
                $form->addElement(
                    'html_editor',
                    'answer[' . $i . ']',
                    null,
                    'style="vertical-align:middle"',
                    array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '100%', 'Height' => '100'));
                $form->addRule('answer[' . $i . ']', get_lang('ThisFieldIsRequired'), 'required');

                // show comment when feedback is enable
                if ($objEx->selectFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM) {
                    $form->addElement('html_editor',
                        'comment[' . $i . ']',
                        null,
                        'style="vertical-align:middle"',
                        array('ToolbarSet' => 'TestProposedAnswer', 'Width' => '100%', 'Height' => '100'));
                }
                $form->addElement('html', '</tr>');
            }

            $form->addElement('html', '</table>');
            $form->addElement('html', '<br />');

            // 3 scores
            $form->addElement('text', 'option[1]', get_lang('Correct'), array('class' => 'span1', 'value' => '1'));
            $form->addElement('text', 'option[2]', get_lang('Wrong'), array('class' => 'span1', 'value' => '-0.5'));

            $form->addElement('hidden', 'option[3]', 0);

            $form->addRule('option[1]', get_lang('ThisFieldIsRequired'), 'required');
            $form->addRule('option[2]', get_lang('ThisFieldIsRequired'), 'required');

            $form->addElement('html', '</tr><table>');
            $form->addElement('hidden', 'options_count', 3);
            $form->addElement('html', '</table><br /><br />');

            //Extra values True, false,  Dont known
            if (!empty($this->extra)) {
                $scores = explode(':', $this->extra);
                if (!empty($scores)) {
                    for ($i = 1; $i <= 3; $i++) {
                        $defaults['option[' . $i . ']'] = $scores[$i - 1];
                    }
                }
            }

            global $text, $class;
            if ($objEx->edit_exercise_in_lp == true) {

                $form->addElement('submit', 'lessAnswers', get_lang('LessAnswer'), 'class="btn minus"');
                $form->addElement('submit', 'moreAnswers', get_lang('PlusAnswer'), 'class="btn plus"');
                $form->addElement('submit', 'submitQuestion', $text, 'class="' . $class . '"');

            }
            $renderer->setElementTemplate('{element}&nbsp;', 'lessAnswers');
            $renderer->setElementTemplate('{element}&nbsp;', 'submitQuestion');
            $renderer->setElementTemplate('{element}&nbsp;', 'moreAnswers');
            $form->addElement('html', '</div></div>');
            $defaults['correct'] = $correct;

            if (!empty($this->id)) {
                $form->setDefaults($defaults);
            } else {
                //if ($this -> isContent == 1) {
                $form->setDefaults($defaults);
                //}
            }
            $form->setConstants(['nb_answers' => $nbAnswers]);
        }

        /**
         * abstract function which creates the form to create / edit the answers of the question
         * @param FormValidator $form
         * @param Exercise $exercise
         */
        public function processAnswersCreation($form, $exercise) {
            $questionWeighting = $nbrGoodAnswers = 0;
            $objAnswer = new Answer($this->id);
            $nbAnswers = $form->getSubmitValue('nb_answers');

            $courseId = api_get_course_int_id();

            $correct = [];
            $options = Question::readQuestionOption($this->id, $courseId);


            if (!empty($options)) {
                foreach ($options as $optionData) {
                    $id = $optionData['id'];
                    unset($optionData['id']);
                    Question::updateQuestionOption($id, $optionData, $courseId);
                }
            } else {
                for ($i = 1; $i <= 8; $i++) {
                    $lastId = Question::saveQuestionOption($this->id, $this->options[$i], $courseId, $i);
                    $correct[$i] = $lastId;
                }
            }

            /* Getting quiz_question_options (true, false, doubt) because
              it's possible that there are more options in the future */

            $newOptions = Question::readQuestionOption($this->id, $courseId);

            $sortedByPosition = [];
            foreach ($newOptions as $item) {
                $sortedByPosition[$item['position']] = $item;
            }

            /* Saving quiz_question.extra values that has the correct scores of
              the true, false, doubt options registered in this format
              XX:YY:ZZZ where XX is a float score value. */
            $extraValues = [];
            
            for ($i = 1; $i <= 3; $i++) {
                $score = trim($form->getSubmitValue('option[' . $i . ']'));
                $extraValues[] = $score;
            }
            $this->setExtra(implode(':', $extraValues));

            for ($i = 1; $i <= $nbAnswers; $i++) {
                $answer = trim($form->getSubmitValue('answer[' . $i . ']'));
                $comment = trim($form->getSubmitValue('comment[' . $i . ']'));
                $goodAnswer = trim($form->getSubmitValue('correct[' . $i . ']'));
                if (empty($options)) {
                    //If this is the first time that the question is created when change the default values from the form 1 and 2 by the correct "option id" registered
                    $goodAnswer = $sortedByPosition[$goodAnswer]['id'];
                }
                $questionWeighting += $extraValues[0]; //By default 0 has the correct answers
                $objAnswer->createAnswer($answer, $goodAnswer, $comment, '', $i);
            }

            // saves the answers into the data base
            $objAnswer->save();

            // sets the total weighting of the question
            $this->updateWeighting($questionWeighting);
            $this->save($exercise);
        }

        /**
         * @param int $feedbackType
         * @param int $counter
         * @param float $score
         * @return null|string
         */
        function return_header($feedbackType = null, $counter = null, $score = null) {
            $header = parent::return_header($feedbackType, $counter, $score);
            $header .= '<table class="'
                . $this->question_table_class
                . '"><tr><th>'
                . get_lang("Choice")
                . '</th><th>'
                . get_lang("ExpectedChoice")
                . '</th><th>'
                . get_lang("Answer")
                . '</th><th>'
                . get_lang("YourDegreeOfCertainty")
                . '</th><th>&nbsp;</th>'
            ;
            if ($feedbackType != EXERCISE_FEEDBACK_TYPE_EXAM) {
                $header .= '<th>' . get_lang("Comment") . '</th>';
            } else {
                $header .= '<th>&nbsp;</th>';
            }
            $header .= '</tr>';
            return $header;
        }

        /**
         * Method to recovery color to show by precision of the student's answer
         * @param $studentAnwser 
         * @param $expectedAnswer 
         * @param $studentDegreeChoicePosition
         * @return string
         */
        function getColorResponse($studentAnwser, $expectedAnswer, $studentDegreeChoicePosition) {
            $checkResult = ($studentAnwser == $expectedAnswer) ? true : false;
            if ($checkResult) {
                if ($studentDegreeChoicePosition >= 6)
                    return '#088A08';
                if ($studentDegreeChoicePosition >= 4 && $studentDegreeChoicePosition <= 5)
                    return '#A9F5A9';
                if ($studentDegreeChoicePosition == 3)
                    return '#FFFFFF';
            } else {
                if ($studentDegreeChoicePosition >= 6)
                    return '#FE2E2E';
                if ($studentDegreeChoicePosition >= 4 && $studentDegreeChoicePosition <= 5)
                    return '#F6CECE';
                if ($studentDegreeChoicePosition == 3)
                    return '#FFFFFF';
            }
        }

        /**
         * Return the color code for student answer
         * @param $studentAnwser
         * @param $expectedAnswer
         * @param $studentDegreeChoicePosition
         * @return int
         */
        function getStatusResponse($studentAnwser, $expectedAnswer, $studentDegreeChoicePosition) {
            $checkResult = ($studentAnwser == $expectedAnswer) ? true : false;
            if ($checkResult) {
                if ($studentDegreeChoicePosition >= 6)
                    return self::LEVEL_DARKGREEN;
                if ($studentDegreeChoicePosition >= 4 && $studentDegreeChoicePosition <= 5)
                    return self::LEVEL_LIGHTGREEN;
                if ($studentDegreeChoicePosition == 3)
                    return self::LEVEL_WHITE;
            } else {
                if ($studentDegreeChoicePosition >= 6)
                    return self::LEVEL_DARKRED;
                if ($studentDegreeChoicePosition >= 4 && $studentDegreeChoicePosition <= 5)
                    return self::LEVEL_LIGHTRED;
                if ($studentDegreeChoicePosition == 3)
                    return self::LEVEL_WHITE;
            }
        }

        /**
         * Method to recovery lable for codes colors
         * @param $studentAnwser
         * @param  $expectedAnswer
         * @param  $studentDegreeChoicePosition
         * @return string
         */
        public function getCodeResponse($studentAnwser, $expectedAnswer, $studentDegreeChoicePosition) {
            $checkResult = ($studentAnwser == $expectedAnswer) ? true : false;
            if ($checkResult) {
                if ($studentDegreeChoicePosition >= 6)
                    return get_lang('langVerySure');
                if ($studentDegreeChoicePosition >= 4 && $studentDegreeChoicePosition <= 5)
                    return get_lang('langPrettySur');
                if ($studentDegreeChoicePosition == 3)
                    return get_lang('langIgnorance');
            } else {
                if ($studentDegreeChoicePosition >= 6)
                    return get_lang('langVeryUnsure');
                if ($studentDegreeChoicePosition >= 4 && $studentDegreeChoicePosition <= 5)
                    return get_lang('langUnsure');
                if ($studentDegreeChoicePosition == 3)
                    return get_lang('langIgnorance');
            }

        }

        /**
         * Method to show the code color and his meaning for the test result
         */
        public static function showColorCode() {
            ?>
            <table class="fc-border-separate" cellspacing="0" style="width:600px ;margin: auto; border: 3px solid #A39E9E;" >

                <tr style="border-bottom: 1px solid #A39E9E;">
                    <td style="width:15%; height:30px; background-color: #088A08; border-right: 1px solid #A39E9E;">&nbsp;</td>
                    <td style="padding-left:10px;">
                        <b><?php echo get_lang('langVerySure'); ?> :</b>
                        <?php echo get_lang('langExplainVerySure'); ?>
                    </td>
                </tr>
                <tr style="border-bottom: 1px solid #A39E9E;">
                    <td style="width:15%; height:30px; background-color: #A9F5A9; border-right: 1px solid #A39E9E;">&nbsp;</td>
                    <td style="padding-left:10px;">
                        <b><?php echo get_lang('langPrettySur'); ?> :</b>
                        <?php echo get_lang('langExplainPrettySur'); ?>
                    </td>
                </tr>
                <tr style="border: 1px solid #A39E9E;">
                    <td style="width:15%; height:30px; background-color: #FFFFFF; border-right: 1px solid #A39E9E;">&nbsp;</td>
                    <td style="padding-left:10px;">
                        <b><?php echo get_lang('langIgnorance'); ?> :</b>
                        <?php echo get_lang('langExplainIgnorance'); ?>
                    </td>
                </tr>
                <tr style="border: 1px solid #A39E9E;">
                    <td style="width:15%; height:30px; background-color: #F6CECE; border-right: 1px solid #A39E9E;">&nbsp;</td>
                    <td style="padding-left:10px;">
                        <b><?php echo get_lang('langUnsure'); ?> :</b>
                        <?php echo get_lang('langExplainUnsure'); ?>
                    </td>
                </tr>
                <tr style="border-bottom: 1px solid #A39E9E;">
                    <td style="width:15%; height:30px; background-color: #FE2E2E; border-right: 1px solid #A39E9E;">&nbsp;</td>
                    <td style="padding-left:10px;">
                        <b><?php echo get_lang('langVeryUnsure'); ?> :</b>
                        <?php echo get_lang('langExplainVeryUnsure'); ?>
                    </td>
                </tr>
            </table><br/>

            <?php

        }


        public static function displayDegreeChartByCategory($scoreListAll, $title, $sizeRatio = 1, $objExercice) {
            $maxHeight = 0;

            if ($objExercice->gather_questions_categories == 1) { // original
                $groupCategoriesByBracket = true;
            } else {
                $groupCategoriesByBracket = false;
            }

            if ($groupCategoriesByBracket) {
                $scoreList = [];
                $categoryPrefixList = [];  // categoryPrefix['Math'] = firstCategoryId for this prefix
                // rebuild $scoreList factirizing datas with caregory prefix
                foreach ($scoreListAll as $categoryId => $scoreListForCategory) {
                    $objCategory = new Testcategory();
                    $objCategoryNum = $objCategory->getCategory($categoryId);
                    preg_match("/^\[([^]]+)\]/", $objCategoryNum->name, $matches);

                    if (count($matches) > 1) {
                        // check if we have already see this prefix
                        if (array_key_exists($matches[1], $categoryPrefixList)) {
                            // add the result color for this entry
                            $scoreList[$categoryPrefixList[$matches[1]]][self::LEVEL_DARKGREEN] += $scoreListForCategory[self::LEVEL_DARKGREEN];
                            $scoreList[$categoryPrefixList[$matches[1]]][self::LEVEL_LIGHTGREEN] += $scoreListForCategory[self::LEVEL_LIGHTGREEN];
                            $scoreList[$categoryPrefixList[$matches[1]]][self::LEVEL_WHITE] += $scoreListForCategory[self::LEVEL_WHITE];
                            $scoreList[$categoryPrefixList[$matches[1]]][self::LEVEL_LIGHTRED] += $scoreListForCategory[self::LEVEL_LIGHTRED];
                            $scoreList[$categoryPrefixList[$matches[1]]][self::LEVEL_DARKRED] += $scoreListForCategory[self::LEVEL_DARKRED];

                        } else {
                            $categoryPrefixList[$matches[1]] = $categoryId;
                            $scoreList[$categoryId] = $scoreListAll[$categoryId];
                        }
                    } else {
                        // dont matche the prefix '[math] Math category'
                        $scoreList[$categoryId] = $scoreListAll[$categoryId];
                    }
                }
            } else {
                $scoreList = $scoreListAll;
            }


            // get the max height of item to have each table the same height if displayed side by side
            foreach ($scoreList as $categoryId => $scoreListForCategory) {
                $testCategorie = new TestCategory();
                $categorieQuestionName = $testCategorie->getCategory($categoryId)->name;
                list($null, $height) = self::displayDegreeChart(
                    $scoreListForCategory,
                    300,
                    '',
                    1,
                    0,
                    false,
                    true,
                    groupCategoriesByBracket
                );
                if ($height > $maxHeight) {
                    $maxHeight = $height;
                }
            }

            if (count($scoreList) > 1) {
                $boxWidth = $sizeRatio * 300 * 2 + 54;
            } else {
                $boxWidth = $sizeRatio * 300 + 54;
            }

            $html = '<div style="width: ' . $boxWidth . 'px; margin : auto; padding-left : 15px;">';
            $html .= '<h3 style="text-align: center; margin : 10px 0">' . $title . '</h3>';

            // get the html of items
            $i = 0;
            foreach ($scoreList as $categoryId => $scoreListForCategory) {
                $oCategory = new Testcategory();
                $categorieQuestionName = $oCategory->getCategory($categoryId)->name;

                if ($categorieQuestionName == '') {
                    $categoryName = get_lang('NonCategory');
                } else {
                    $categoryName = $oCategory->name;
                }

                $html .= '<div style="float:left; margin-right: 10px;">';
                $html .= self::displayDegreeChart(
                    $scoreListForCategory,
                    300,
                    $categoryName,
                    1,
                    $maxHeight,
                    false,
                    false,
                    $groupCategoriesByBracket
                );
                $html .= '</div>';

                if ($i == 1) {
                    $html .= '<div style="clear:both">&nbsp;</div>';
                    $i = 0;
                } else {
                    $i++;
                }
            }


            return $html . '<div style="clear:both">&nbsp;</div></div>';
        }

        /**
         * Return HTML code for the $scoreList of MultipleAnswerTrueFalseDegreeCertainty questions
         * @param $scoreList
         * @param int $sizeRatio
         * @return string
         */
        public static function displayDegreeChart(
            $scoreList,
            $widthTable,
            $title = '',
            $sizeRatio = 1,
            $minHeight = 0,
            $displayExplanationText = true,
            $returnHeight = false,
            $groupCategoriesByBracket = false,
            $numberOfQuestions = 0
        ) {
            $topAndBottomMargin = 10;

            $colorList = [self::LEVEL_DARKRED,
                self::LEVEL_LIGHTRED,
                self::LEVEL_WHITE,
                self::LEVEL_LIGHTGREEN,
                self::LEVEL_DARKGREEN
            ];


            // get total attempt number
            $highterColorHeight = 0;

            foreach ($scoreList as $color => $number) {
                if ($number > $highterColorHeight) {
                    $highterColorHeight = $number;
                }
            }

            $totalAttemptNumber = $numberOfQuestions;

            $verticalLineHeight = $highterColorHeight * $sizeRatio * 2 + 122 + $topAndBottomMargin * 2;
            if ($verticalLineHeight < $minHeight) {
                $minHeightCorrection = $minHeight - $verticalLineHeight;
                $verticalLineHeight += $minHeightCorrection;
            }

            // draw chart
            $html = '<style type="text/css">
                    .certaintyQuizBox {
                        border : 1px solid black;
                        margin : auto;
                    }

                    .certaintyQuizColumn {
                        float : left;
                    }

                    /* text at the top of the column */
                    .certaintyQuizDivMiddle {
                        height : 20px;
                        margin : 0;
                        padding : 0;
                        text-align: center
                    }

                    .certaintyQuizDivBottom {
                        border : 1px solid black;
                    }

                    .certaintyVerticalLine {
                        float: left;
                        border-left : 1px solid black;
                        font-size: 0;
                    }

                    .certaintyQuizClearer {
                        clear : both;
                        font-size: 0;
                        height:0
                    }

                    .certaintyQuizLevel_' . self::LEVEL_DARKGREEN . ' {
                        background-color: #088A08;
                    }

                    .certaintyQuizLevel_' . self::LEVEL_LIGHTGREEN . ' {
                        background-color: #A9F5A9;
                    }

                    .certaintyQuizLevel_' . self::LEVEL_WHITE . ' {
                        background-color: #FFFFFF;
                         width:88%
                    }

                    .certaintyQuizLevel_' . self::LEVEL_LIGHTRED . ' {
                        background-color: #F6CECE;
                    }

                    .certaintyQuizLevel_' . self::LEVEL_DARKRED . ' {
                        background-color: #FE2E2E;
                    }
                    
                    table.certaintyTable {
                        margin : auto; 
                        border: 1px solid #999A9B;
                    }
                    
                    table.certaintyTable th {
                        text-align: center; 
                        border-bottom: 1px solid #999A9B;
                        background-color: #cdd0d4;
                        padding : 10px;                    
                    }
                    
                    table.certaintyTable td {
                        padding : 10px;                    
                    }
                    
                    
                    table.certaintyTable td.borderRight {
                        border-right: 1px dotted #000000; 
                    }
                    
                    table.certaintyTable td.firstLine {
                        vertical-align: top;
                        text-align: center;
                    }
                    
                    table.certaintyTable th.globalChart {
                        font-size : 18pt;
                        line-height : 120%;
                        padding : 20px;
                    }
                    
                    table.certaintyTable td.globalChart {
                        font-weight : bold;
                    }
                    
                </style>';

            if ($groupCategoriesByBracket) {
                $title = api_preg_replace("/[^]]*$/", "", $title);
                $title = ucfirst(api_preg_replace("/[\[\]]/", "", $title));
            }

            $affiche = (strpos($title, "ensemble") > 0) ?
                $title . "<br/>($totalAttemptNumber questions)" :
                $title;
            $textSize = (
                strpos($title, "ensemble") > 0 ||
                strpos($title, "votre dernier résultat à ce test") > 0
            ) ? 100 : 80;

            if ($displayExplanationText) {
                // global chart
                $classGlobalChart = "globalChart";
            } else {
                $classGlobalChart = "";
            }

            $html .= '<table class="certaintyTable" style="height : '
                . $verticalLineHeight
                . 'px; width : '
                . $widthTable.'px;">'
            ;
            $html .= '<tr><th colspan="5" class="'
                . $classGlobalChart
                . '">'
                . $affiche
                . '</th><tr>'
            ;

            $nbResponsesInc = (isset($scoreList[4]) || isset($scoreList[5])) ? $scoreList[4] + $scoreList[5] : 0;
            $nbResponsesIng = (isset($scoreList[3])) ? $scoreList[3] : 0;
            $nbResponsesCor = (isset($scoreList[1]) || isset($scoreList[2])) ? $scoreList[1] + $scoreList[2] : 0;

            $colWidth = $widthTable / 5;

            $html .= '<tr>
                    <td class="firstLine borderRight '.$classGlobalChart.'" 
                        colspan="2" 
                        style="width:'.($colWidth * 2).'px; font-size:'.$textSize.'%;">'.
                get_lang('langWrongsAnswers').'&nbsp;: '.$nbResponsesInc.'
                    </td>
                    <td class="firstLine borderRight '.$classGlobalChart.'" 
                        style="width:'.$colWidth.'px; font-size :'.$textSize.'%;">'.
                get_lang('langIgnoranceAnswers').'&nbsp;: '.$nbResponsesIng.'
                    </td>
                    <td class="firstLine '.$classGlobalChart.'" 
                        colspan="2" 
                        style="width:'.($colWidth * 2).'px; font-size:'.$textSize.'%;">'.
                get_lang('langCorrectsAnswers').'&nbsp;: '.$nbResponsesCor.'
                    </td>
                </tr>';
            $html .= '<tr>';

            foreach ($colorList as $i => $color) {
                if (array_key_exists($color, $scoreList)) {
                    $scoreOnBottom = $scoreList[$color]; // height of the colored area on the bottom
                } else {
                    $scoreOnBottom = 0;
                }

                $sizeOnBottom = $scoreOnBottom * $sizeRatio * 2 ;

                if ($i == 1 || $i == 2) {
                    $html .= '<td width="'
                        . $colWidth
                        . 'px" style="border-right: 1px dotted #000000; vertical-align: bottom;font-size: '
                        . $textSize
                        . '%;">'
                    ;
                } else {
                    $html .= '<td width="'
                        . $colWidth
                        . 'px" style="vertical-align: bottom;font-size: '
                        . $textSize
                        . '%;">'
                    ;
                }
                $html .= '<div class="certaintyQuizDivMiddle">'
                    . $scoreOnBottom
                    . '</div><div class="certaintyQuizDivBottom certaintyQuizLevel_'
                    . $color
                    . '" style="height: '
                    . $sizeOnBottom
                    . 'px;">&nbsp;</div>'
                ;
                $html .= '</td>';
            }

            $html .= '</tr>';

            if ($displayExplanationText) {
                // affichage texte histogramme
                $explainHistoList = array(
                    'langVeryUnsure',
                    'langUnsure',
                    'langIgnorance',
                    'langPrettySur',
                    'langVerySure');
                $html .= '<tr>';
                $i = 0;
                foreach ($explainHistoList as $explain) {
                    if ($i == 1 || $i == 2) {
                        $class = "borderRight";
                    } else {
                        $class = "";
                    }
                    $html .= '<td class="firstLine '
                        . $class
                        . ' '
                        . $classGlobalChart
                        . '" style="width="'
                        . $colWidth
                        . 'px; font-size:'
                        . $textSize
                        . '%;">'
                    ;
                    $html .= get_lang($explain);
                    $html .= '</td>';
                    $i++;
                }
                $html .= '</tr>';
            }

            $html .='</table></center>';

            if ($returnHeight) {
                return array($html, $verticalLineHeight);
            } else {
                return $html;
            }
        }

        /**
         * return previous attempt id for this test for student, 0 if no previous attempt
         * @param $exeId
         * @return int
         */
        public static function getPreviousAttemptId($exeId) {
            $tblTrackEExercise = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
            $sql = "SELECT *
                FROM $tblTrackEExercise
                WHERE exe_id = " . intval($exeId);
            $res = Database::query($sql);

            if (Database::num_rows($res) == 0) {
                // if we cannot find the exe_id
                return 0;
            }

            $data = Database::fetch_assoc($res);
            $courseCode = $data['exe_cours_id'];
            $exerciseId = $data['exe_exo_id'];
            $userId = $data['exe_user_id'];
            $attemptDate = $data['exe_date'];

            if ($attemptDate == "0000-00-00 00:00:00") {
                // incomplete attempt, close it before continue
                return 0;
            }

            // look for previous attempt
            $sql = "SELECT *
                FROM $tblTrackEExercise
                WHERE c_id = '" . $courseCode . "'
                AND exe_exo_id = " . intval($exerciseId) . "
                AND exe_user_id = " . intval($userId) . "
                AND status = ''
                AND exe_date > '0000-00-00 00:00:00'
                AND exe_date < '" . $attemptDate . "'
                ORDER BY exe_date DESC";

            $res = Database::query($sql);

            if (Database::num_rows($res) == 0) {
                // no previous attempt
                return 0;
            }

            $data = Database::fetch_assoc($res);
            return $data['exe_id'];
        }

        /**
         * return an array of number of answer color for exe attempt for question type = MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY
         * e.g.
         * [LEVEL_DARKGREEN => 3, LEVEL_LIGHTGREEN => 0, LEVEL_WHITE => 5, LEVEL_LIGHTRED => 12, LEVEL_DARKTRED => 0]
         * @param $exeId
         * @return array
         */
        public static function getColorNumberListForAttempt($exeId) {
            $result = [self::LEVEL_DARKGREEN => 0,
                self::LEVEL_LIGHTGREEN => 0,
                self::LEVEL_WHITE => 0,
                self::LEVEL_LIGHTRED => 0,
                self::LEVEL_DARKRED => 0
            ];

            $attemptInfoList = self::getExerciseAttemptInfo($exeId);

            foreach ($attemptInfoList as $i => $attemptInfo) {
                $oQuestion = new MultipleAnswerTrueFalseDegreeCertainty();
                $oQuestion->read($attemptInfo['question_id']);
                if ($oQuestion->type == MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY) {
                    $answerColor = self::getAnswerColor($exeId, $attemptInfo['question_id'], $attemptInfo['position']);
                    if ($answerColor) {
                        $result[$answerColor] ++;
                    }
                }
            }
            return $result;
        }

        /**
         * return an array of number of color for question type = MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY
         * for each question category
         *
         * e.g.
         * [
         *      (categoryId=)5 => [LEVEL_DARKGREEN => 3, LEVEL_WHITE => 5, LEVEL_LIGHTRED => 12]
         *      (categoryId=)2 => [LEVEL_DARKGREEN => 8, LEVEL_LIGHTRED => 2, LEVEL_DARKTRED => 8]
         *      (categoryId=)0 => [LEVEL_DARKGREEN => 1, LEVEL_LIGHTGREEN => 2, LEVEL_WHITE => 6, LEVEL_LIGHTRED => 1, LEVEL_DARKTRED => 9]
         * ]
         * @param $exeId
         * @return array
         */
        public static function getColorNumberListForAttemptByCategory($exeId) {
            $result = array();
            $attemptInfoList = self::getExerciseAttemptInfo($exeId);

            foreach ($attemptInfoList as $i => $attemptInfo) {
                $oQuestion = new MultipleAnswerTrueFalseDegreeCertainty();
                $oQuestion->read($attemptInfo['question_id']);
                if ($oQuestion->type == MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY) {
                    $questionCategory = Testcategory::getCategoryForQuestion($attemptInfo['question_id']);

                    if (!array_key_exists($questionCategory, $result)) {
                        $result[$questionCategory] = array();
                    }

                    $answerColor = self::getAnswerColor($exeId, $attemptInfo['question_id'], $attemptInfo['position']);
                    if ($answerColor) {
                        $result[$questionCategory][$answerColor] ++;
                    }
                }
            }
            return $result;
        }

        /**
         * Return true if answer of $exeId, $questionId, $position is correct, otherwise return false
         * @param $exeId
         * @param $questionId
         * @param $position
         * @return bool
         */
        public static function getAnswerColor($exeId, $questionId, $position) {
            $attemptInfoList = self::getExerciseAttemptInfo($exeId, $questionId, $position);

            if (count($attemptInfoList) != 1) {
                // havent got the answer
                return 0;
            }

            $answerCodes = $attemptInfoList[0]['answer'];

            // student answer
            $splitAnswer = preg_split("/:/", $answerCodes);
            // get correct answer option id
            $correctAnswerOptionId = self::getCorrectAnswerOptionId($splitAnswer[0]);
            if ($correctAnswerOptionId == 0) {
                // error returning the correct answer option id
                return 0;
            }

            // get student answer option id
            $studentAnswerOptionId = $splitAnswer[1];

            // we got the correct answer option id, let's compare ti with the student answer
            $percentage = self::getPercentagePosition($splitAnswer[2]);
            if ($studentAnswerOptionId == $correctAnswerOptionId) {
                // yeah, student got correct answer
                switch ($percentage) {
                    case 3 :
                        return self::LEVEL_WHITE;
                    case 4 :
                    case 5 :
                        return self::LEVEL_LIGHTGREEN;
                    case 6 :
                    case 7 :
                    case 8 :
                        return self::LEVEL_DARKGREEN;
                    default :
                        return 0;
                }
            } else {
                // bummer, wrong answer dude
                switch ($percentage) {
                    case 3 :
                        return self::LEVEL_WHITE;
                    case 4 :
                    case 5 :
                        return self::LEVEL_LIGHTRED;
                    case 6 :
                    case 7 :
                    case 8 :
                        return self::LEVEL_DARKRED;
                    default :
                        return 0;
                }
            }
        }

        /**
         * Return the position of certitude %age choose by student
         * @param $optionId
         * @return int
         */
        public static function getPercentagePosition($optionId) {
            $tblAnswerOption = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);
            $courseId = api_get_course_int_id();
            $sql = "SELECT position
                FROM $tblAnswerOption
                WHERE c_id = " . intval($courseId) . "
                AND id = " . intval($optionId);
            $res = Database::query($sql);

            if (Database::num_rows($res) == 0) {
                return 0;
            }

            $data = Database::fetch_assoc($res);
            return $data['position'];
        }

        /**
         * return the correct id from c_quiz_question_option for question idAuto
         * @param $idAuto
         * @return int
         */
        public static function getCorrectAnswerOptionId($idAuto) {
            $tblAnswer = Database::get_course_table(TABLE_QUIZ_ANSWER);
            $courseId = api_get_course_int_id();
            $sql = "SELECT correct
                FROM $tblAnswer
                WHERE c_id = " . intval($courseId) . "
                AND id_auto = " . intval($idAuto);

            $res = Database::query($sql);
            $data = Database::fetch_assoc($res);
            if (Database::num_rows($res) > 0) {
                return $data['correct'];
            } else {
                return 0;
            }
        }

        /**
         * return an array of exe info from track_e_attempt
         * @param $exeId
         * @param int $questionId
         * @param int $position
         * @return array
         */
        public static function getExerciseAttemptInfo($exeId, $questionId = -1, $position = -1) {
            $result = array();
            $and = '';

            if ($questionId >= 0) {
                $and .= " AND question_id = " . intval($questionId);
            }
            if ($position >= 0) {
                $and .= " AND position = " . intval($position);
            }

            $tblExeAttempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
            $cId = api_get_course_int_id();
            $sql = "SELECT * FROM $tblExeAttempt
                WHERE c_id = $cId
                AND exe_id = $exeId
                $and";

            $res = Database::query($sql);
            while ($data = Database::fetch_assoc($res)) {
                $result[] = $data;
            }
            return $result;
        }


        public static function getNumberOfQuestionsForExeId($exeId)
        {
            $tableTrackEExercise = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
            $sql = "SELECT exe_exo_id
            FROM $tableTrackEExercise
            WHERE exe_id=".intval($exeId);
            $res = Database::query($sql);
            $data = Database::fetch_assoc($res);
            $exerciseId = $data['exe_exo_id'];

            $objectExercise = new Exercise();
            $objectExercise->read($exerciseId);

            return $objectExercise->get_count_question_list();
        }

        /**
         * Display student chart results for these question types
         * @param $exeId
         * @return string
         */
        public static function displayStudentsChartResults($exeId, $objExercice) {

            $numberOfQuestions = self::getNumberOfQuestionsForExeId($exeId);

            $globalScoreList = MultipleAnswerTrueFalseDegreeCertainty::getColorNumberListForAttempt($exeId);
            $html =  MultipleAnswerTrueFalseDegreeCertainty::displayDegreeChart(
                    $globalScoreList,
                    600,
                    get_lang('ResultTest'),
                    2,
                    0,
                    true,
                    false,
                    false,
                    $numberOfQuestions
                )
                . "<br/>"
            ;

            $previousAttemptId = MultipleAnswerTrueFalseDegreeCertainty::getPreviousAttemptId($exeId);
            if ($previousAttemptId > 0) {
                $previousAttemptScoreList = MultipleAnswerTrueFalseDegreeCertainty::getColorNumberListForAttempt(
                    $previousAttemptId
                );
                $html .= MultipleAnswerTrueFalseDegreeCertainty::displayDegreeChart(
                    $previousAttemptScoreList,
                    600,
                    get_lang('CompareLastResult'),
                    2
                    )
                    . "<br/>"
                ;
            }

            $categoryScoreList = MultipleAnswerTrueFalseDegreeCertainty::getColorNumberListForAttemptByCategory($exeId);
            $html .= MultipleAnswerTrueFalseDegreeCertainty::displayDegreeChartByCategory(
                $categoryScoreList,
                get_lang('ResultsbyDiscipline'),
                1
                ,$objExercice
                )
                . "<br/>"
            ;

            return $html;
        }

    }
