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
    public $speeds = [
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
        $wordsPerMinute = $this->speeds[$this->level];
        if ($wordsPerMinute == 0) {
            $wordsPerMinute = 1;
        }
        return $this->wordsCount/$wordsPerMinute;
    }
}
