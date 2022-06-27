<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class MultipleAnswerTrueFalseDegreeCertainty
 * This class allows to instantiate an object of type MULTIPLE_ANSWER
 * (MULTIPLE CHOICE, MULTIPLE ANSWER), extending the class question.
 */
class MultipleAnswerTrueFalseDegreeCertainty extends Question
{
    public const LEVEL_DARKGREEN = 1;
    public const LEVEL_LIGHTGREEN = 2;
    public const LEVEL_WHITE = 3;
    public const LEVEL_LIGHTRED = 4;
    public const LEVEL_DARKRED = 5;

    public $typePicture = 'mccert.png';
    public $explanationLangVar = 'MultipleAnswerTrueFalseDegreeCertainty';
    public $optionsTitle;
    public $options;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY;
        $this->isContent = $this->getIsContent();
        $this->optionsTitle = [1 => 'Answers', 2 => 'DegreeOfCertaintyThatMyAnswerIsCorrect'];
        $this->options = [
            1 => 'True',
            2 => 'False',
            3 => '50%',
            4 => '60%',
            5 => '70%',
            6 => '80%',
            7 => '90%',
            8 => '100%',
        ];
    }

    /**
     * Redefines Question::createAnswersForm: creates the HTML form to answer the question.
     *
     * @uses \globals $text and $class, defined in the calling script
     *
     * @param FormValidator $form
     *
     * @throws Exception
     * @throws HTML_QuickForm_Error
     */
    public function createAnswersForm($form)
    {
        global $text;
        $nbAnswers = isset($_POST['nb_answers']) ? (int) $_POST['nb_answers'] : 4;
        // The previous default value was 2. See task #1759.
        $nbAnswers += (isset($_POST['lessAnswers']) ? -1 : (isset($_POST['moreAnswers']) ? 1 : 0));

        $courseId = api_get_course_int_id();
        $objEx = Session::read('objExercise');
        $renderer = &$form->defaultRenderer();
        $defaults = [];

        $form->addHeader(get_lang('Answers'));
        $html = '<table class="table table-striped table-hover">
            <tr>
                <th width="10px">'.get_lang('Number').'</th>
                <th width="10px">'.get_lang('True').'</th>
                <th width="10px">'.get_lang('False').'</th>
                <th width="50%">'.get_lang('Answer').'</th>';

        // show column comment when feedback is enable
        if ($objEx->getFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM) {
            $html .= '<th width="50%">'.get_lang('Comment').'</th>';
        }
        $html .= '</tr>';

        $form->addHtml($html);

        $correct = 0;
        $answer = null;
        if (!empty($this->iid)) {
            $answer = new Answer($this->iid);
            $answer->read();
            if ($answer->nbrAnswers > 0 && !$form->isSubmitted()) {
                $nbAnswers = $answer->nbrAnswers;
            }
        }

        $form->addElement('hidden', 'nb_answers');
        $boxesNames = [];

        if ($nbAnswers < 1) {
            $nbAnswers = 1;
            echo Display::return_message(get_lang('YouHaveToCreateAtLeastOneAnswer'));
        }

        // Can be more options
        $optionData = Question::readQuestionOption($this->iid, $courseId);

        for ($i = 1; $i <= $nbAnswers; $i++) {
            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                'correct['.$i.']'
            );
            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                'counter['.$i.']'
            );
            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                'answer['.$i.']'
            );
            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                'comment['.$i.']'
            );

            $answerNumber = $form->addElement('text', 'counter['.$i.']', null, 'value="'.$i.'"');
            $answerNumber->freeze();

            $defaults['answer['.$i.']'] = '';
            $defaults['comment['.$i.']'] = '';
            $defaults['correct['.$i.']'] = '';

            if (is_object($answer)) {
                $defaults['answer['.$i.']'] = isset($answer->answer[$i]) ? $answer->answer[$i] : '';
                $defaults['comment['.$i.']'] = isset($answer->comment[$i]) ? $answer->comment[$i] : '';
                $defaults['weighting['.$i.']'] = isset($answer->weighting[$i]) ? float_format($answer->weighting[$i], 1) : '';
                $correct = isset($answer->correct[$i]) ? $answer->correct[$i] : '';
                $defaults['correct['.$i.']'] = $correct;

                $j = 1;
                if (!empty($optionData)) {
                    foreach ($optionData as $id => $data) {
                        $rdoCorrect = $form->addElement('radio', 'correct['.$i.']', null, null, $id);

                        if (isset($_POST['correct']) && isset($_POST['correct'][$i]) && $id == $_POST['correct'][$i]) {
                            $rdoCorrect->setValue(Security::remove_XSS($_POST['correct'][$i]));
                        }

                        $j++;
                        if ($j == 3) {
                            break;
                        }
                    }
                }
            } else {
                $form->addElement('radio', 'correct['.$i.']', null, null, 1);
                $form->addElement('radio', 'correct['.$i.']', null, null, 2);
            }

            $boxesNames[] = 'correct['.$i.']';
            $txtAnswer = $form->addElement(
                'html_editor',
                'answer['.$i.']',
                null,
                ['style' => 'vertical-align:middle;'],
                ['ToolbarSet' => 'TestProposedAnswer', 'Width' => '100%', 'Height' => '100']
            );
            $form->addRule('answer['.$i.']', get_lang('ThisFieldIsRequired'), 'required');

            if (isset($_POST['answer']) && isset($_POST['answer'][$i])) {
                $txtAnswer->setValue(Security::remove_XSS($_POST['answer'][$i]));
            }

            // show comment when feedback is enable
            if ($objEx->getFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM) {
                $txtComment = $form->addElement(
                    'html_editor',
                    'comment['.$i.']',
                    null,
                    ['style' => 'vertical-align:middle;'],
                    ['ToolbarSet' => 'TestProposedAnswer', 'Width' => '100%', 'Height' => '100']
                );

                if (isset($_POST['comment']) && isset($_POST['comment'][$i])) {
                    $txtComment->setValue(Security::remove_XSS($_POST['comment'][$i]));
                }
            }
            $form->addElement('html', '</tr>');
        }

        $form->addElement('html', '</table>');
        $form->addElement('html', '<br />');

        // 3 scores
        $txtOption1 = $form->addElement('text', 'option[1]', get_lang('Correct'), ['value' => '1']);
        $txtOption2 = $form->addElement('text', 'option[2]', get_lang('Wrong'), ['value' => '-0.5']);

        $form->addElement('hidden', 'option[3]', 0);

        $form->addRule('option[1]', get_lang('ThisFieldIsRequired'), 'required');
        $form->addRule('option[2]', get_lang('ThisFieldIsRequired'), 'required');

        $form->addElement('html', '</tr><table>');
        $form->addElement('hidden', 'options_count', 3);
        $form->addElement('html', '</table><br /><br />');

        //Extra values True, false, Don't known
        if (!empty($this->extra)) {
            $scores = explode(':', $this->extra);
            if (!empty($scores)) {
                $txtOption1->setValue($scores[0]);
                $txtOption2->setValue($scores[1]);
            }
        }

        if ($objEx->edit_exercise_in_lp === true ||
            (empty($this->exerciseList) && empty($objEx->iid))
        ) {
            $form->addElement('submit', 'lessAnswers', get_lang('LessAnswer'), 'class="btn btn-danger minus"');
            $form->addElement('submit', 'moreAnswers', get_lang('PlusAnswer'), 'class="btn btn-primary plus"');
            $form->addElement('submit', 'submitQuestion', $text, 'class = "btn btn-primary"');
        }
        $renderer->setElementTemplate('{element}&nbsp;', 'lessAnswers');
        $renderer->setElementTemplate('{element}&nbsp;', 'submitQuestion');
        $renderer->setElementTemplate('{element}&nbsp;', 'moreAnswers');
        $form->addElement('html', '</div></div>');

        if (!empty($this->iid) && !$form->isSubmitted()) {
            $form->setDefaults($defaults);
        }

        $form->setConstants(['nb_answers' => $nbAnswers]);
    }

    /**
     * abstract function which creates the form to create / edit the answers of the question.
     *
     * @param FormValidator $form
     * @param Exercise      $exercise
     */
    public function processAnswersCreation($form, $exercise)
    {
        $questionWeighting = 0;
        $objAnswer = new Answer($this->iid);
        $nbAnswers = $form->getSubmitValue('nb_answers');
        $courseId = api_get_course_int_id();
        $correct = [];
        $options = Question::readQuestionOption($this->iid, $courseId);

        if (!empty($options)) {
            foreach ($options as $optionData) {
                $id = $optionData['iid'];
                unset($optionData['iid']);
                Question::updateQuestionOption($id, $optionData, $courseId);
            }
        } else {
            for ($i = 1; $i <= 8; $i++) {
                $lastId = Question::saveQuestionOption($this->iid, $this->options[$i], $courseId, $i);
                $correct[$i] = $lastId;
            }
        }

        /* Getting quiz_question_options (true, false, doubt) because
          it's possible that there are more options in the future */
        $newOptions = Question::readQuestionOption($this->iid, $courseId);
        $sortedByPosition = [];
        foreach ($newOptions as $item) {
            $sortedByPosition[$item['position']] = $item;
        }

        /* Saving quiz_question.extra values that has the correct scores of
          the true, false, doubt options registered in this format
          XX:YY:ZZZ where XX is a float score value. */
        $extraValues = [];
        for ($i = 1; $i <= 3; $i++) {
            $score = trim($form->getSubmitValue('option['.$i.']'));
            $extraValues[] = $score;
        }
        $this->setExtra(implode(':', $extraValues));

        for ($i = 1; $i <= $nbAnswers; $i++) {
            $answer = trim($form->getSubmitValue('answer['.$i.']'));
            $comment = trim($form->getSubmitValue('comment['.$i.']'));
            $goodAnswer = trim($form->getSubmitValue('correct['.$i.']'));
            if (empty($options)) {
                // If this is the first time that the question is created then change
                // the default values from the form 1 and 2 by the correct "option id" registered
                $goodAnswer = $sortedByPosition[$goodAnswer]['iid'];
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
     * {@inheritdoc}
     */
    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $header = parent::return_header($exercise, $counter, $score);
        $header .= '<table class="'.$this->question_table_class.'"><tr>';
        $header .= '<th>'.get_lang('Choice').'</th>';

        if ($exercise->showExpectedChoiceColumn()) {
            $header .= '<th>'.get_lang('ExpectedChoice').'</th>';
        }

        $header .= '<th>'
            .get_lang('Answer')
            .'</th><th colspan="2" style="text-align:center;">'
            .get_lang('YourDegreeOfCertainty')
            .'</th>'
        ;

        if (false === $exercise->hideComment) {
            if ($exercise->getFeedbackType() != EXERCISE_FEEDBACK_TYPE_EXAM) {
                $header .= '<th>'.get_lang('Comment').'</th>';
            }
        }
        $header .= '</tr>';

        return $header;
    }

    /**
     * Get color code, status, label and description for the current answer.
     *
     * @param string $studentAnswer
     * @param string $expectedAnswer
     * @param int    $studentDegreeChoicePosition
     *
     * @return array An array with indexes 'color', 'background-color', 'status', 'label' and 'description'
     */
    public function getResponseDegreeInfo($studentAnswer, $expectedAnswer, $studentDegreeChoicePosition)
    {
        $result = [];
        if ($studentDegreeChoicePosition == 3) {
            $result = [
                'color' => '#000000',
                'background-color' => '#F6BA2A',
                'status' => self::LEVEL_WHITE,
                'label' => get_lang('DegreeOfCertaintyDeclaredIgnorance'),
                'description' => get_lang('DegreeOfCertaintyDeclaredIgnoranceDescription'),
            ];
        } else {
            $checkResult = $studentAnswer == $expectedAnswer ? true : false;
            if ($checkResult) {
                if ($studentDegreeChoicePosition >= 6) {
                    $result = [
                        'color' => '#FFFFFF',
                        'background-color' => '#1E9C55',
                        'status' => self::LEVEL_DARKGREEN,
                        'label' => get_lang('DegreeOfCertaintyVerySure'),
                        'description' => get_lang('DegreeOfCertaintyVerySureDescription'),
                    ];
                } elseif ($studentDegreeChoicePosition >= 4 && $studentDegreeChoicePosition <= 5) {
                    $result = [
                        'color' => '#000000',
                        'background-color' => '#B1E183',
                        'status' => self::LEVEL_LIGHTGREEN,
                        'label' => get_lang('DegreeOfCertaintyPrettySure'),
                        'description' => get_lang('DegreeOfCertaintyPrettySureDescription'),
                    ];
                }
            } else {
                if ($studentDegreeChoicePosition >= 6) {
                    $result = [
                        'color' => '#FFFFFF',
                        'background-color' => '#ED4040',
                        'status' => self::LEVEL_DARKRED,
                        'label' => get_lang('DegreeOfCertaintyVeryUnsure'),
                        'description' => get_lang('DegreeOfCertaintyVeryUnsureDescription'),
                    ];
                } elseif ($studentDegreeChoicePosition >= 4 && $studentDegreeChoicePosition <= 5) {
                    $result = [
                        'color' => '#000000',
                        'background-color' => '#F79B88',
                        'status' => self::LEVEL_LIGHTRED,
                        'label' => get_lang('DegreeOfCertaintyUnsure'),
                        'description' => get_lang('DegreeOfCertaintyUnsureDescription'),
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Method to show the code color and his meaning for the test result.
     */
    public static function showColorCodes()
    {
        ?>
        <table class="fc-border-separate" cellspacing="0" style="width:600px;
            margin: auto; border: 3px solid #A39E9E;" >
            <tr style="border-bottom: 1px solid #A39E9E;">
                <td style="width:15%; height:30px; background-color: #088A08; border-right: 1px solid #A39E9E;">
                    &nbsp;
                </td>
                <td style="padding-left:10px;">
                    <b><?php echo get_lang('DegreeOfCertaintyVerySure'); ?> :</b>
                    <?php echo get_lang('DegreeOfCertaintyVerySureDescription'); ?>
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #A39E9E;">
                <td style="width:15%; height:30px; background-color: #A9F5A9; border-right: 1px solid #A39E9E;">
                    &nbsp;
                </td>
                <td style="padding-left:10px;">
                    <b><?php echo get_lang('DegreeOfCertaintyPrettySure'); ?> :</b>
                    <?php echo get_lang('DegreeOfCertaintyPrettySureDescription'); ?>
                </td>
            </tr>
            <tr style="border: 1px solid #A39E9E;">
                <td style="width:15%; height:30px; background-color: #FFFFFF; border-right: 1px solid #A39E9E;">
                    &nbsp;
                </td>
                <td style="padding-left:10px;">
                    <b><?php echo get_lang('DegreeOfCertaintyDeclaredIgnorance'); ?> :</b>
                    <?php echo get_lang('DegreeOfCertaintyDeclaredIgnoranceDescription'); ?>
                </td>
            </tr>
            <tr style="border: 1px solid #A39E9E;">
                <td style="width:15%; height:30px; background-color: #F6CECE; border-right: 1px solid #A39E9E;">
                    &nbsp;
                </td>
                <td style="padding-left:10px;">
                    <b><?php echo get_lang('DegreeOfCertaintyUnsure'); ?> :</b>
                    <?php echo get_lang('DegreeOfCertaintyUnsureDescription'); ?>
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #A39E9E;">
                <td style="width:15%; height:30px; background-color: #FE2E2E; border-right: 1px solid #A39E9E;">
                    &nbsp;
                </td>
                <td style="padding-left:10px;">
                    <b><?php echo get_lang('DegreeOfCertaintyVeryUnsure'); ?> :</b>
                    <?php echo get_lang('DegreeOfCertaintyVeryUnsureDescription'); ?>
                </td>
            </tr>
        </table><br/>
        <?php
    }

    /**
     * Display basic bar charts of results by category of questions.
     *
     * @param array  $scoreListAll
     * @param string $title        The block title
     * @param int    $sizeRatio
     *
     * @return string The HTML/CSS code for the charts block
     */
    public static function displayDegreeChartByCategory($scoreListAll, $title, $sizeRatio = 1)
    {
        $maxHeight = 0;
        $groupCategoriesByBracket = false;
        if ($groupCategoriesByBracket) {
            $scoreList = [];
            $categoryPrefixList = [];
            // categoryPrefix['Math'] = firstCategoryId for this prefix
            // rebuild $scoreList factorizing data with category prefix
            foreach ($scoreListAll as $categoryId => $scoreListForCategory) {
                $objCategory = new Testcategory();
                $objCategoryNum = $objCategory->getCategory($categoryId);
                preg_match("/^\[([^]]+)\]/", $objCategoryNum->name, $matches);

                if (count($matches) > 1) {
                    // check if we have already see this prefix
                    if (array_key_exists($matches[1], $categoryPrefixList)) {
                        // add the result color for this entry
                        $scoreList[$categoryPrefixList[$matches[1]]][self::LEVEL_DARKGREEN] +=
                            $scoreListForCategory[self::LEVEL_DARKGREEN];
                        $scoreList[$categoryPrefixList[$matches[1]]][self::LEVEL_LIGHTGREEN] +=
                            $scoreListForCategory[self::LEVEL_LIGHTGREEN];
                        $scoreList[$categoryPrefixList[$matches[1]]][self::LEVEL_WHITE] +=
                            $scoreListForCategory[self::LEVEL_WHITE];
                        $scoreList[$categoryPrefixList[$matches[1]]][self::LEVEL_LIGHTRED] +=
                            $scoreListForCategory[self::LEVEL_LIGHTRED];
                        $scoreList[$categoryPrefixList[$matches[1]]][self::LEVEL_DARKRED] +=
                            $scoreListForCategory[self::LEVEL_DARKRED];
                    } else {
                        $categoryPrefixList[$matches[1]] = $categoryId;
                        $scoreList[$categoryId] = $scoreListAll[$categoryId];
                    }
                } else {
                    // doesn't match the prefix '[math] Math category'
                    $scoreList[$categoryId] = $scoreListAll[$categoryId];
                }
            }
        } else {
            $scoreList = $scoreListAll;
        }

        // get the max height of item to have each table the same height if displayed side by side
        $testCategory = new TestCategory();
        foreach ($scoreList as $categoryId => $scoreListForCategory) {
            $category = $testCategory->getCategory($categoryId);
            if ($category) {
                $categoryQuestionName = $category->name;
            }
            list($noValue, $height) = self::displayDegreeChartChildren(
                $scoreListForCategory,
                300,
                '',
                1,
                0,
                false,
                true,
                0
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

        $html = '<div class="row-chart">';
        $html .= '<h4 class="chart-title">'.$title.'</h4>';

        $legendTitle = [
            'DegreeOfCertaintyVeryUnsure',
            'DegreeOfCertaintyUnsure',
            'DegreeOfCertaintyDeclaredIgnorance',
            'DegreeOfCertaintyPrettySure',
            'DegreeOfCertaintyVerySure',
        ];
        $html .= '<ul class="chart-legend">';
        foreach ($legendTitle as $i => $item) {
            $html .= '<li><i class="fa fa-square square_color'.$i.'" aria-hidden="true"></i> '.get_lang($item).'</li>';
        }
        $html .= '</ul>';

        // get the html of items
        $i = 0;
        $testCategory = new Testcategory();
        foreach ($scoreList as $categoryId => $scoreListForCategory) {
            $category = $testCategory->getCategory($categoryId);
            $categoryQuestionName = '';
            if ($category) {
                $categoryQuestionName = $category->name;
            }

            if ($categoryQuestionName === '') {
                $categoryName = get_lang('WithoutCategory');
            } else {
                $categoryName = $categoryQuestionName;
            }

            $html .= '<div class="col-md-4">';
            $html .= self::displayDegreeChartChildren(
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

            if ($i == 2) {
                $html .= '<div style="clear:both; height: 10px;">&nbsp;</div>';
                $i = 0;
            } else {
                $i++;
            }
        }
        $html .= '</div>';

        return $html.'<div style="clear:both; height: 10px;" >&nbsp;</div>';
    }

    /**
     * Return HTML code for the $scoreList of MultipleAnswerTrueFalseDegreeCertainty questions.
     *
     * @param        $scoreList
     * @param        $widthTable
     * @param string $title
     * @param int    $sizeRatio
     * @param int    $minHeight
     * @param bool   $displayExplanationText
     * @param bool   $returnHeight
     * @param bool   $groupCategoriesByBracket
     * @param int    $numberOfQuestions
     *
     * @return array|string
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
        $colorList = [
            self::LEVEL_DARKRED,
            self::LEVEL_LIGHTRED,
            self::LEVEL_WHITE,
            self::LEVEL_LIGHTGREEN,
            self::LEVEL_DARKGREEN,
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
        $html = '';

        if ($groupCategoriesByBracket) {
            $title = api_preg_replace("/[^]]*$/", '', $title);
            $title = ucfirst(api_preg_replace("/[\[\]]/", '', $title));
        }

        $titleDisplay = (strpos($title, "ensemble") > 0) ?
            $title."<br/>($totalAttemptNumber questions)" :
            $title;
        $textSize = (
            strpos($title, 'ensemble') > 0 ||
            strpos($title, 'votre dernier résultat à ce test') > 0
        ) ? 100 : 80;

        $html .= '<div class="row-chart">';
        $html .= '<h4 class="chart-title">'.$titleDisplay.'</h4>';

        $nbResponsesInc = 0;
        if (isset($scoreList[4])) {
            $nbResponsesInc += (int) $scoreList[4];
        }
        if (isset($scoreList[5])) {
            $nbResponsesInc += (int) $scoreList[5];
        }

        $nbResponsesIng = isset($scoreList[3]) ? $scoreList[3] : 0;

        $nbResponsesCor = 0;
        if (isset($scoreList[1])) {
            $nbResponsesCor += (int) $scoreList[1];
        }
        if (isset($scoreList[2])) {
            $nbResponsesCor += (int) $scoreList[2];
        }

        $IncorrectAnswers = sprintf(get_lang('IncorrectAnswersX'), $nbResponsesInc);
        $IgnoranceAnswers = sprintf(get_lang('IgnoranceAnswersX'), $nbResponsesIng);
        $CorrectAnswers = sprintf(get_lang('CorrectAnswersX'), $nbResponsesCor);

        $html .= '<div class="chart-grid">';

        $explainHistoList = null;
        if ($displayExplanationText) {
            // Display of histogram text
            $explainHistoList = [
                'DegreeOfCertaintyVeryUnsure',
                'DegreeOfCertaintyUnsure',
                'DegreeOfCertaintyDeclaredIgnorance',
                'DegreeOfCertaintyPrettySure',
                'DegreeOfCertaintyVerySure',
            ];
        }

        foreach ($colorList as $i => $color) {
            if (array_key_exists($color, $scoreList)) {
                $scoreOnBottom = $scoreList[$color]; // height of the colored area on the bottom
            } else {
                $scoreOnBottom = 0;
            }
            $sizeBar = ($scoreOnBottom * $sizeRatio * 2).'px;';

            if ($i == 0) {
                $html .= '<div class="item">';
                $html .= '<div class="panel-certaint" style="min-height:'.$verticalLineHeight.'px; position: relative;">';
                $html .= '<div class="answers-title">'.$IncorrectAnswers.'</div>';
                $html .= '<ul class="certaint-list-two">';
            } elseif ($i == 3) {
                $html .= '<div class="item">';
                $html .= '<div class="panel-certaint" style="height:'.$verticalLineHeight.'px;  position: relative;">';
                $html .= '<div class="answers-title">'.$CorrectAnswers.'</div>';
                $html .= '<ul class="certaint-list-two">';
            } elseif ($i == 2) {
                $html .= '<div class="item">';
                $html .= '<div class="panel-certaint" style="height:'.$verticalLineHeight.'px;  position: relative;">';
                $html .= '<div class="answers-title">'.$IgnoranceAnswers.'</div>';
                $html .= '<ul class="certaint-list">';
            }
            $html .= '<li>';
            $html .= '<div class="certaint-score">';
            $html .= $scoreOnBottom;
            $html .= '</div>';
            $html .= '<div class="levelbar_'.$color.'" style="height:'.$sizeBar.'">&nbsp;</div>';
            $html .= '<div class="certaint-text">'.get_lang($explainHistoList[$i]).'</div>';
            $html .= '</li>';

            if ($i == 1 || $i == 2 || $i == 4) {
                $html .= '</ul>';
                $html .= '</div>';
                $html .= '</div>';
            }
        }

        $html .= '</div>';
        $html .= '</div>';

        if ($returnHeight) {
            return [$html, $verticalLineHeight];
        } else {
            return $html;
        }
    }

    /**
     * Return HTML code for the $scoreList of MultipleAnswerTrueFalseDegreeCertainty questions.
     *
     * @param        $scoreList
     * @param        $widthTable
     * @param string $title
     * @param int    $sizeRatio
     * @param int    $minHeight
     * @param bool   $displayExplanationText
     * @param bool   $returnHeight
     * @param bool   $groupCategoriesByBracket
     * @param int    $numberOfQuestions
     *
     * @return array|string
     */
    public static function displayDegreeChartChildren(
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
        $colorList = [
            self::LEVEL_DARKRED,
            self::LEVEL_LIGHTRED,
            self::LEVEL_WHITE,
            self::LEVEL_LIGHTGREEN,
            self::LEVEL_DARKGREEN,
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
        $html = '';

        if ($groupCategoriesByBracket) {
            $title = api_preg_replace("/[^]]*$/", '', $title);
            $title = ucfirst(api_preg_replace("/[\[\]]/", '', $title));
        }

        $textSize = 80;

        $classGlobalChart = '';
        if ($displayExplanationText) {
            // global chart
            $classGlobalChart = 'globalChart';
        }

        $html .= '<table class="certaintyTable" style="height :'.$verticalLineHeight.'px; margin-bottom: 10px;" >';
        $html .= '<tr><th colspan="5" class="'.$classGlobalChart.'">'
            .$title
            .'</th><tr>'
        ;

        $nbResponsesInc = 0;
        if (isset($scoreList[4])) {
            $nbResponsesInc += (int) $scoreList[4];
        }
        if (isset($scoreList[5])) {
            $nbResponsesInc += (int) $scoreList[5];
        }

        $nbResponsesIng = isset($scoreList[3]) ? $scoreList[3] : 0;

        $nbResponsesCor = 0;
        if (isset($scoreList[1])) {
            $nbResponsesCor += (int) $scoreList[1];
        }
        if (isset($scoreList[2])) {
            $nbResponsesCor += (int) $scoreList[2];
        }

        $colWidth = $widthTable / 5;

        $html .= '<tr>
                <td class="firstLine borderRight '.$classGlobalChart.'"
                    colspan="2"
                    style="width:'.($colWidth * 2).'px; line-height: 15px; font-size:'.$textSize.'%;">'.
            sprintf(get_lang('IncorrectAnswersX'), $nbResponsesInc).'
                </td>
                <td class="firstLine borderRight '.$classGlobalChart.'"
                    style="width:'.$colWidth.'px; line-height: 15px; font-size :'.$textSize.'%;">'.
            sprintf(get_lang('IgnoranceAnswersX'), $nbResponsesIng).'
                </td>
                <td class="firstLine '.$classGlobalChart.'"
                    colspan="2"
                    style="width:'.($colWidth * 2).'px; line-height: 15px; font-size:'.$textSize.'%;">'.
            sprintf(get_lang('CorrectAnswersX'), $nbResponsesCor).'
                </td>
            </tr>';
        $html .= '<tr>';

        foreach ($colorList as $i => $color) {
            if (array_key_exists($color, $scoreList)) {
                $scoreOnBottom = $scoreList[$color]; // height of the colored area on the bottom
            } else {
                $scoreOnBottom = 0;
            }
            $sizeOnBottom = $scoreOnBottom * $sizeRatio * 2;
            if ($i == 1 || $i == 2) {
                $html .= '<td width="'
                    .$colWidth
                    .'px" style="border-right: 1px dotted #7FC5FF; vertical-align: bottom;font-size: '
                    .$textSize
                    .'%;">'
                ;
            } else {
                $html .= '<td width="'
                    .$colWidth
                    .'px" style="vertical-align: bottom;font-size: '
                    .$textSize
                    .'%;">'
                ;
            }
            $html .= '<div class="certaint-score">'
                .$scoreOnBottom
                .'</div><div class="levelbar_'
                .$color
                .'" style="height: '
                .$sizeOnBottom
                .'px;">&nbsp;</div>'
            ;
            $html .= '</td>';
        }

        $html .= '</tr>';

        if ($displayExplanationText) {
            // Display of histogram text
            $explainHistoList = [
                'DegreeOfCertaintyVeryUnsure',
                'DegreeOfCertaintyUnsure',
                'DegreeOfCertaintyDeclaredIgnorance',
                'DegreeOfCertaintyPrettySure',
                'DegreeOfCertaintyVerySure',
            ];
            $html .= '<tr>';
            $i = 0;
            foreach ($explainHistoList as $explain) {
                if ($i == 1 || $i == 2) {
                    $class = 'borderRight';
                } else {
                    $class = '';
                }
                $html .= '<td class="firstLine '
                    .$class
                    .' '
                    .$classGlobalChart
                    .'" style="width="'
                    .$colWidth
                    .'px; font-size:'
                    .$textSize
                    .'%;">'
                ;
                $html .= get_lang($explain);
                $html .= '</td>';
                $i++;
            }
            $html .= '</tr>';
        }
        $html .= '</table></center>';

        if ($returnHeight) {
            return [$html, $verticalLineHeight];
        } else {
            return $html;
        }
    }

    /**
     * return previous attempt id for this test for student, 0 if no previous attempt.
     *
     * @param $exeId
     *
     * @return int
     */
    public static function getPreviousAttemptId($exeId)
    {
        $tblTrackEExercise = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $exeId = (int) $exeId;
        $sql = "SELECT * FROM $tblTrackEExercise
                WHERE exe_id = ".$exeId;
        $res = Database::query($sql);

        if (empty(Database::num_rows($res))) {
            // if we cannot find the exe_id
            return 0;
        }

        $data = Database::fetch_assoc($res);
        $courseCode = $data['c_id'];
        $exerciseId = $data['exe_exo_id'];
        $userId = $data['exe_user_id'];
        $attemptDate = $data['exe_date'];

        if ($attemptDate == '0000-00-00 00:00:00') {
            // incomplete attempt, close it before continue
            return 0;
        }

        // look for previous attempt
        $exerciseId = (int) $exerciseId;
        $userId = (int) $userId;
        $sql = "SELECT *
            FROM $tblTrackEExercise
            WHERE c_id = '$courseCode'
            AND exe_exo_id = $exerciseId
            AND exe_user_id = $userId
            AND status = ''
            AND exe_date > '0000-00-00 00:00:00'
            AND exe_date < '$attemptDate'
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
     * return an array of number of answer color for exe attempt
     * for question type = MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY
     * e.g.
     * [LEVEL_DARKGREEN => 3, LEVEL_LIGHTGREEN => 0, LEVEL_WHITE => 5, LEVEL_LIGHTRED => 12, LEVEL_DARKTRED => 0].
     *
     * @param $exeId
     *
     * @return array
     */
    public static function getColorNumberListForAttempt($exeId)
    {
        $result = [
            self::LEVEL_DARKGREEN => 0,
            self::LEVEL_LIGHTGREEN => 0,
            self::LEVEL_WHITE => 0,
            self::LEVEL_LIGHTRED => 0,
            self::LEVEL_DARKRED => 0,
        ];

        $attemptInfoList = self::getExerciseAttemptInfo($exeId);

        foreach ($attemptInfoList as $attemptInfo) {
            $oQuestion = new MultipleAnswerTrueFalseDegreeCertainty();
            $oQuestion->read($attemptInfo['question_id']);
            if ($oQuestion->type == MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY) {
                $answerColor = self::getAnswerColor($exeId, $attemptInfo['question_id'], $attemptInfo['position']);
                if ($answerColor) {
                    $result[$answerColor]++;
                }
            }
        }

        return $result;
    }

    /**
     * return an array of number of color for question type = MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY
     * for each question category.
     *
     * e.g.
     * [
     *      (categoryId=)5 => [LEVEL_DARKGREEN => 3, LEVEL_WHITE => 5, LEVEL_LIGHTRED => 12]
     *      (categoryId=)2 => [LEVEL_DARKGREEN => 8, LEVEL_LIGHTRED => 2, LEVEL_DARKTRED => 8]
     *      (categoryId=)0 => [LEVEL_DARKGREEN => 1,
     *          LEVEL_LIGHTGREEN => 2,
     *          LEVEL_WHITE => 6,
     *          LEVEL_LIGHTRED => 1,
     *          LEVEL_DARKTRED => 9]
     * ]
     *
     * @param int $exeId
     *
     * @return array
     */
    public static function getColorNumberListForAttemptByCategory($exeId)
    {
        $result = [];
        $attemptInfoList = self::getExerciseAttemptInfo($exeId);

        foreach ($attemptInfoList as $attemptInfo) {
            $oQuestion = new MultipleAnswerTrueFalseDegreeCertainty();
            $oQuestion->read($attemptInfo['question_id']);
            if ($oQuestion->type == MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY) {
                $questionCategory = Testcategory::getCategoryForQuestion($attemptInfo['question_id']);

                if (!array_key_exists($questionCategory, $result)) {
                    $result[$questionCategory] = [];
                }

                $answerColor = self::getAnswerColor($exeId, $attemptInfo['question_id'], $attemptInfo['position']);
                if ($answerColor && isset($result[$questionCategory])) {
                    if (!isset($result[$questionCategory][$answerColor])) {
                        $result[$questionCategory][$answerColor] = 0;
                    }
                    $result[$questionCategory][$answerColor]++;
                }
            }
        }

        return $result;
    }

    /**
     * Return true if answer of $exeId, $questionId, $position is correct, otherwise return false.
     *
     * @param $exeId
     * @param $questionId
     * @param $position
     *
     * @return int
     */
    public static function getAnswerColor($exeId, $questionId, $position)
    {
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
        $studentAnswerOptionId = isset($splitAnswer[1]) ? $splitAnswer[1] : null;

        // we got the correct answer option id, let's compare ti with the student answer
        $percentage = null;
        if (isset($splitAnswer[2])) {
            $percentage = self::getPercentagePosition($splitAnswer[2]);
        }

        if ($studentAnswerOptionId == $correctAnswerOptionId) {
            // yeah, student got correct answer
            switch ($percentage) {
                case 3:
                    return self::LEVEL_WHITE;
                case 4:
                case 5:
                    return self::LEVEL_LIGHTGREEN;
                case 6:
                case 7:
                case 8:
                    return self::LEVEL_DARKGREEN;
                default:
                    return 0;
            }
        } else {
            // bummer, wrong answer dude
            switch ($percentage) {
                case 3:
                    return self::LEVEL_WHITE;
                case 4:
                case 5:
                    return self::LEVEL_LIGHTRED;
                case 6:
                case 7:
                case 8:
                    return self::LEVEL_DARKRED;
                default:
                    return 0;
            }
        }
    }

    /**
     * Return the position of certitude %age choose by student.
     *
     * @param $optionId
     *
     * @return int
     */
    public static function getPercentagePosition($optionId)
    {
        $tblAnswerOption = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);
        $optionId = (int) $optionId;
        $sql = "SELECT position
                FROM $tblAnswerOption
                WHERE iid = $optionId";
        $res = Database::query($sql);

        if (Database::num_rows($res) == 0) {
            return 0;
        }

        $data = Database::fetch_assoc($res);

        return $data['position'];
    }

    /**
     * return the correct id from c_quiz_question_option for question idAuto.
     *
     * @param $idAuto
     *
     * @return int
     */
    public static function getCorrectAnswerOptionId($idAuto)
    {
        $tblAnswer = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $idAuto = (int) $idAuto;
        $sql = "SELECT correct FROM $tblAnswer
                WHERE id_auto = $idAuto";

        $res = Database::query($sql);
        $data = Database::fetch_assoc($res);
        if (Database::num_rows($res) > 0) {
            return $data['correct'];
        } else {
            return 0;
        }
    }

    /**
     * return an array of exe info from track_e_attempt.
     *
     * @param int $exeId
     * @param int $questionId
     * @param int $position
     *
     * @return array
     */
    public static function getExerciseAttemptInfo($exeId, $questionId = -1, $position = -1)
    {
        $result = [];
        $and = '';
        $questionId = (int) $questionId;
        $position = (int) $position;
        $exeId = (int) $exeId;

        if ($questionId >= 0) {
            $and .= " AND question_id = $questionId";
        }
        if ($position >= 0) {
            $and .= " AND position = $position";
        }

        $tblExeAttempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $cId = api_get_course_int_id();
        $sql = "SELECT * FROM $tblExeAttempt
                WHERE c_id = $cId AND exe_id = $exeId $and";

        $res = Database::query($sql);
        while ($data = Database::fetch_assoc($res)) {
            $result[] = $data;
        }

        return $result;
    }

    /**
     * @param int $exeId
     *
     * @return int
     */
    public static function getNumberOfQuestionsForExeId($exeId)
    {
        $tableTrackEExercise = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $exeId = (int) $exeId;

        $sql = "SELECT exe_exo_id
                FROM $tableTrackEExercise
                WHERE exe_id = ".$exeId;
        $res = Database::query($sql);
        $data = Database::fetch_assoc($res);
        if ($data) {
            $exerciseId = $data['exe_exo_id'];

            $objectExercise = new Exercise();
            $objectExercise->read($exerciseId);

            return $objectExercise->getQuestionCount();
        }

        return 0;
    }

    /**
     * Display student chart results for these question types.
     *
     * @param int      $exeId
     * @param Exercise $objExercice
     *
     * @return string
     */
    public static function displayStudentsChartResults($exeId, $objExercice)
    {
        $numberOfQuestions = self::getNumberOfQuestionsForExeId($exeId);
        $globalScoreList = self::getColorNumberListForAttempt($exeId);
        $html = self::displayDegreeChart(
            $globalScoreList,
            600,
            get_lang('YourOverallResultForTheTest'),
            2,
            0,
            true,
            false,
            false,
            $numberOfQuestions
        );
        $html .= '<br/>';

        $previousAttemptId = self::getPreviousAttemptId($exeId);
        if ($previousAttemptId > 0) {
            $previousAttemptScoreList = self::getColorNumberListForAttempt(
                $previousAttemptId
            );
            $html .= self::displayDegreeChart(
                $previousAttemptScoreList,
                600,
                get_lang('ForComparisonYourLastResultToThisTest'),
                2
            );
            $html .= '<br/>';
        }

        $list = self::getColorNumberListForAttemptByCategory($exeId);
        $html .= self::displayDegreeChartByCategory(
            $list,
            get_lang('YourResultsByDiscipline'),
            1,
            $objExercice
        );
        $html .= '<br/>';

        return $html;
    }

    /**
     * send mail to student with degre certainty result test.
     *
     * @param int      $userId
     * @param Exercise $objExercise
     * @param int      $exeId
     */
    public static function sendQuestionCertaintyNotification($userId, $objExercise, $exeId)
    {
        $userInfo = api_get_user_info($userId);
        $recipientName = api_get_person_name($userInfo['firstname'],
            $userInfo['lastname'],
            null,
            PERSON_NAME_EMAIL_ADDRESS
        );
        $subject = "[".get_lang('DoNotReply')."] "
            .html_entity_decode(get_lang('ResultAccomplishedTest')." \"".$objExercise->title."\"");

        // message sended to the student
        $message = get_lang('Dear').' '.$recipientName.",<br /><br />";
        $exerciseLink = "<a href='".api_get_path(WEB_CODE_PATH)."/exercise/result.php?show_headers=1&"
            .api_get_cidreq()
            ."&id=$exeId'>";
        $exerciseTitle = $objExercise->title;

        $message .= sprintf(
            get_lang('MessageQuestionCertainty'),
            $exerciseTitle,
            api_get_path(WEB_PATH),
            $exerciseLink
        );

        // show histogram
        $message .= self::displayStudentsChartResults($exeId, $objExercise);
        $message .= get_lang('KindRegards');
        $message = api_preg_replace("/\\\n/", '', $message);

        MessageManager::send_message_simple($userId, $subject, $message);
    }
}
