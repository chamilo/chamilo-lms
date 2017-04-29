<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class ReadingSpeed
 *
 * This class allows to instantiate an object of type READING_SPEED
 * extending the class question
 *
 * @package chamilo.exercise
 **/
class ReadingSpeed extends UniqueAnswer
{
    public static $typePicture = 'reading-speed.png';
    public static $explanationLangVar = 'ReadingComprehension';

    /**
     * Defines the different speeds of scrolling for the reading window,
     * in words per minute. If 300 words text in 50w/m, then the moving
     * window will progress from top to bottom in 6 minutes
     * @var array $speeds
     */
    public static $speeds = [
        1 => 50,
        2 => 100,
        3 => 175,
        4 => 250,
        5 => 400
    ];
    /**
     * The number of words in the question description (which serves as the
     * text to read)
     * @var int $wordsCount
     */
    private $wordsCount = 0;
    public $expectedCount = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = READING_SPEED;
        $this->isContent = $this->getIsContent();
    }

    /**
     * Returns the speed, in pixels per second, at which the moving
     * window should scroll automatically
     * @param int $textHeight The height of the text area in pixels
     * @return int The number of pixels per second (speed) to scroll down
     */
    private function calculateSpeed($textHeight)
    {
        if (empty($this->wordsCount) or empty($textHeight)) {
            return 0;
        }
        $wordsPerMinute = $this->expectedCount;
        if ($wordsPerMinute == 0) {
            $wordsPerMinute = 1;
        }
        return $this->wordsCount/$wordsPerMinute;
    }

    public function createAnswersForm($form)
    {
        $form->addTextarea('text', get_lang('Text'), ['rows' => '15']);

        parent::createAnswersForm($form);
    }

    public function processAnswersCreation($form)
    {
        $text = $form->exportValue('text');

        $objAnswer = new Answer($this->id);
        $objAnswer->createAnswer($text, false, null, 0, 0);

        $questionWeighting = $nbrGoodAnswers = 0;
        $correct = $form->getSubmitValue('correct');
        $nb_answers = $form->getSubmitValue('nb_answers');

        for ($i = 1; $i <= $nb_answers; $i++) {
            $answer = trim($form->getSubmitValue('answer[' . $i . ']'));
            $comment = trim($form->getSubmitValue('comment[' . $i . ']'));
            $weighting = trim($form->getSubmitValue('weighting[' . $i . ']'));
            $scenario = $form->getSubmitValue('scenario');

            $try = $scenario['try' . $i];
            $lp = $scenario['lp' . $i];
            $destination = $scenario['destination' . $i];
            $url = trim($scenario['url' . $i]);

            $goodAnswer = $correct == $i ? true : false;

            if ($goodAnswer) {
                $nbrGoodAnswers++;
                $weighting = abs($weighting);

                if ($weighting > 0) {
                    $questionWeighting += $weighting;
                }
            }

            if (empty($try)) {
                $try = 0;
            }

            if (empty($lp)) {
                $lp = 0;
            }

            if (empty($destination)) {
                $destination = 0;
            }

            if ($url == '') {
                $url = 0;
            }

            //1@@1;2;@@2;4;4;@@http://www.chamilo.org
            $dest = $try . '@@' . $lp . '@@' . $destination . '@@' . $url;
            $objAnswer->createAnswer($answer, $goodAnswer, $comment, $weighting, $i, null, null, $dest);
        }

        // saves the answers into the data base
        $objAnswer->save();

        // sets the total weighting of the question
        $this->updateWeighting($questionWeighting);
        $this->save();
    }

    private function displayReading($wordsCount, $turns, $text)
    {
        $view = new Template('', false, false, false, true, false, false);

        $template = $view->get_template('exercise/reading_speed.tpl');

        $view->assign('id', $this->id);
        $view->assign('text', $text);
        $view->assign('words_count', $wordsCount);
        $view->assign('turns', $turns);
        $view->display($template);
    }

    public function processText($text)
    {
        //recalulate the expected words count
        $this->expectedCount = self::$speeds[$this->level];

        $words = str_word_count($text, 2, '0..9');
        $indexes = array_keys($words);

        $tagEnd = '</span>';
        $tagStart = $tagEnd.'<span class="text-highlight">';

        $turns = ceil(
            count($words) / $this->expectedCount
        );

        $firstIndex = $indexes[0];

        for ($i = 1; $i <= $turns; $i++) {
            $text = substr_replace($text, $tagStart, $firstIndex, 0);

            if ($i * $this->expectedCount <= count($words)) {
                $nextFirstIndex = $indexes[$i * $this->expectedCount];

                $firstIndex = $nextFirstIndex + (strlen($tagStart) * $i);
            }
        }

        $pos = strpos($text, $tagEnd);

        $text = substr_replace($text, '', $pos, strlen($tagEnd));
        $text .= $tagEnd;

        $this->displayReading(count($words), $turns, $text);
    }
}
