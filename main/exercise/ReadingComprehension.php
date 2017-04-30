<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class ReadingComprehension
 *
 * This class allows to instantiate an object of type READING_COMPREHENSION
 * extending the class question
 *
 * @package chamilo.exercise
 **/
class ReadingComprehension extends UniqueAnswer
{
    public static $typePicture = 'reading-comprehension.png';
    public static $explanationLangVar = 'ReadingComprehension';

    /**
     * Defines the different speeds of scrolling for the reading window,
     * in words per minute. If 300 words text in 50w/m, then the moving
     * window will progress from top to bottom in 6 minutes
     * @var array $speeds
     */
    public $speeds = [
        1 => 50,
        2 => 100,
        3 => 175,
        4 => 300,
        5 => 600
    ];
    /**
     * The number of words in the question description (which serves as the
     * text to read)
     * @var int $wordsCount
     */
    public $wordsCount = 0;
    /**
     * Number of words expected to show per refresh
     * @var int
     */
    public $expectedWordsPerRefresh = 0;
    /**
     * Refresh delay in seconds
     * @var int
     */
    public $refreshTime = 3;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = READING_COMPREHENSION;
        $this->isContent = $this->getIsContent();
    }

    private function displayReading($wordsCount, $turns, $text)
    {
        $view = new Template('', false, false, false, true, false, false);

        $template = $view->get_template('exercise/reading_comprehension.tpl');

        $view->assign('id', $this->id);
        $view->assign('text', $text);
        $view->assign('words_count', $wordsCount);
        $view->assign('turns', $turns);
        $view->assign('refreshTime', $this->refreshTime);
        $view->display($template);
    }

    public function processText($text)
    {
        // Refresh is set to 5s, but speed is in words per minute
        $wordsPerSecond = $this->speeds[$this->level] / 60;
        $this->expectedWordsPerRefresh = intval($wordsPerSecond * $this->refreshTime);

        if (empty($text)) {
            // We have an issue here... how do we treat this case?
            // For now, let's define a default case
            $text = get_lang('NoExercise');
        }
        $words = str_word_count($text, 2, '0..9');
        $indexes = array_keys($words);

        $tagEnd = '</span>';
        $tagStart = $tagEnd.'<span class="text-highlight blur">';
        $this->wordsCount = count($words);

        $turns = ceil(
            $this->wordsCount / $this->expectedWordsPerRefresh
        );

        $firstIndex = $indexes[0];

        for ($i = 1; $i <= $turns; $i++) {
            $text = substr_replace($text, $tagStart, $firstIndex, 0);

            if ($i * $this->expectedWordsPerRefresh <= count($words)) {
                $newIndex = $i * $this->expectedWordsPerRefresh;
                if (isset($indexes[$newIndex])) {
                    $nextFirstIndex = $indexes[$newIndex];
                    $firstIndex = $nextFirstIndex + (strlen($tagStart) * $i);
                }
            }
        }

        $pos = strpos($text, $tagEnd);

        $text = substr_replace($text, '', $pos, strlen($tagEnd));
        $text .= $tagEnd;

        $this->displayReading($this->wordsCount, $turns, $text);
    }
    /**
     * Returns total count of words of the text to read
     * @return int
     */
    public function getWordsCount() {
        $words = str_word_count($this->selectDescription(), 2, '0..9');
        $this->wordsCount = count($words);
        return $this->wordsCount;
    }
}
