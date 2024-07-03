
<?php

/* For licensing terms, see /license.txt */

/**
 * Class ReadingComprehension.
 *
 * This class allows to instantiate an object of type READING_COMPREHENSION
 * extending the class question
 */
class ReadingComprehension extends UniqueAnswer
{
    public $typePicture = 'reading-comprehension.png';
    public $explanationLangVar = 'ReadingComprehension';

    /**
     * Defines the different speeds of scrolling for the reading window,
     * in words per minute. If 300 words text in 50w/m, then the moving
     * window will progress from top to bottom in 6 minutes.
     *
     * @var array
     */
    public static $speeds = [
        1 => 50,
        2 => 100,
        3 => 175,
        4 => 300,
        5 => 600,
    ];

    /**
     * The number of words in the question description (which serves as the
     * text to read).
     *
     * @var int
     */
    public $wordsCount = 0;

    /**
     * Number of words expected to show per refresh.
     *
     * @var int
     */
    public $expectedWordsPerRefresh = 0;

    /**
     * Refresh delay in seconds.
     *
     * @var int
     */
    public $refreshTime = 3;
    /**
     * All speeds (static $speeds + extra speeds defined in configuration.php as 'exercise_question_reading_comprehension_extra_speeds'.
     *
     * @var array
     */
    public $allSpeeds = [];

    /**
     * Indicates how show the question list.
     * 1 = all in one page; 2 = one per page (default).
     *
     * @var int
     */
    private $exerciseType = 2;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = READING_COMPREHENSION;
        $this->isContent = $this->getIsContent();
        $extraSpeeds = api_get_configuration_value('exercise_question_reading_comprehension_extra_speeds');
        $customSpeeds = $extraSpeeds['speeds'] ?? [];
        $this->allSpeeds = self::$speeds;
        if (!empty($customSpeeds) && is_array($customSpeeds)) {
            foreach ($customSpeeds as $speed) {
                $this->allSpeeds[] = $speed;
            }
            asort($this->allSpeeds);
        }
    }

    public function processText($text)
    {
        // Refresh is set to 5s, but speed is in words per minute
        $wordsPerSecond = $this->allSpeeds[$this->level] / 60;
        $this->expectedWordsPerRefresh = intval($wordsPerSecond * $this->refreshTime);

        if (empty($text)) {
            // We have an issue here... how do we treat this case?
            // For now, let's define a default case
            $text = get_lang('NoExercise');
        }
        $words = str_word_count($text, 2, '0..9');
        $indexes = array_keys($words);

        $tagEnd = '</span>';
        $tagStart = $tagEnd.'<span class="text-highlight">';
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
     * Returns total count of words of the text to read.
     *
     * @return int
     */
    public function getWordsCount()
    {
        $words = str_word_count($this->selectDescription(), 2, '0..9');
        $this->wordsCount = count($words);

        return $this->wordsCount;
    }

    /**
     * {@inheritdoc}
     */
    public function createForm(&$form, $exercise)
    {
        // Categories
        $tabCat = TestCategory::getCategoriesIdAndName();
        $form->addSelect('questionCategory', get_lang('Category'), $tabCat);
        // Advanced parameters
        $levels = $this->getReadingSpeeds();
        $form->addSelect('questionLevel', get_lang('Difficulty'), $levels);
        $form->addElement('hidden', 'answerType', READING_COMPREHENSION);
        $form->addTextarea('questionDescription', get_lang('Text'), ['rows' => 20]);
        // question name
        if (api_get_configuration_value('save_titles_as_html')) {
            $editorConfig = ['ToolbarSet' => 'TitleAsHtml'];
            $form->addHtmlEditor(
                'questionName',
                get_lang('Question'),
                false,
                false,
                $editorConfig,
                true
            );
        } else {
            $form->addText('questionName', get_lang('Question'), false);
        }

        // hidden values
        $form->addRule('questionName', get_lang('GiveQuestion'), 'required');
        $isContent = isset($_REQUEST['isContent']) ? (int) $_REQUEST['isContent'] : null;

        // default values
        $defaults = [];
        $defaults['questionName'] = $this->question;
        $defaults['questionDescription'] = $this->description;
        $defaults['questionLevel'] = $this->level;
        $defaults['questionCategory'] = $this->category;

        // Came from he question pool
        if (isset($_GET['fromExercise'])) {
            $form->setDefaults($defaults);
        }

        if (!isset($_GET['newQuestion']) || $isContent) {
            $form->setDefaults($defaults);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function get_default_levels()
    {
        return [
            1 => sprintf(get_lang('ReadingComprehensionLevelX'), self::$speeds[1]),
            2 => sprintf(get_lang('ReadingComprehensionLevelX'), self::$speeds[2]),
            3 => sprintf(get_lang('ReadingComprehensionLevelX'), self::$speeds[3]),
            4 => sprintf(get_lang('ReadingComprehensionLevelX'), self::$speeds[4]),
            5 => sprintf(get_lang('ReadingComprehensionLevelX'), self::$speeds[5]),
        ];
    }

    /**
     * Return the augmented speeds (using, if defined, the 'exercise_question_reading_comprehension_extra_speeds' setting.
     */
    public function getReadingSpeeds(): array
    {
        $defaultLevels = [];
        foreach ($this->allSpeeds as $i => $v) {
            $defaultLevels[$i] = sprintf(get_lang('ReadingComprehensionLevelX'), $this->allSpeeds[$i]);
        }

        return $defaultLevels;
    }

    /**
     * @param int $type
     *
     * @return ReadingComprehension
     */
    public function setExerciseType($type)
    {
        $this->exerciseType = (int) $type;

        return $this;
    }

    /**
     * @param $wordsCount
     * @param $turns
     * @param $text
     */
    private function displayReading($wordsCount, $turns, $text)
    {
        $view = new Template('', false, false, false, true, false, false);

        $template = $view->get_template('exercise/reading_comprehension.tpl');

        $view->assign('id', $this->iid);
        $view->assign('text', nl2br($text));
        $view->assign('words_count', $wordsCount);
        $view->assign('turns', $turns);
        $view->assign('refresh_time', $this->refreshTime);
        $view->assign('exercise_type', $this->exerciseType);
        $view->display($template);
    }
}
