<?php

/* For licensing terms, see /license.txt */

/**
 * Class OralExpression
 * This class allows to instantiate an object of type FREE_ANSWER,
 * extending the class question.
 *
 * @author Eric Marguin
 */
class OralExpression extends Question
{
    public $typePicture = 'audio_question.png';
    public $explanationLangVar = 'OralExpression';
    public $available_extensions = ['wav', 'ogg'];
    private $sessionId;
    private $userId;
    private $exerciseId;
    private $exeId;
    private $storePath;
    private $fileName;
    private $filePath;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = ORAL_EXPRESSION;
        $this->isContent = $this->getIsContent();
    }

    /**
     * {@inheritdoc}
     */
    public function createAnswersForm($form)
    {
        $form->addText(
            'weighting',
            get_lang('Weighting'),
            ['class' => 'span1']
        );
        global $text;
        // setting the save button here and not in the question class.php
        $form->addButtonSave($text, 'submitQuestion');
        if (!empty($this->iid)) {
            $form->setDefaults(['weighting' => float_format($this->weighting, 1)]);
        } else {
            if ($this->isContent == 1) {
                $form->setDefaults(['weighting' => '10']);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processAnswersCreation($form, $exercise)
    {
        $this->weighting = $form->getSubmitValue('weighting');
        $this->save($exercise);
    }

    /**
     * {@inheritdoc}
     */
    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $score['revised'] = $this->isQuestionWaitingReview($score);
        $header = parent::return_header($exercise, $counter, $score);
        $header .= '<table class="'.$this->question_table_class.'">
            <tr>
                <th>'.get_lang("Answer").'</th>
            </tr>';

        return $header;
    }

    /**
     * Initialize the attributes to generate the file path.
     */
    public function initFile(
        int $sessionId,
        int $userId,
        int $exerciseId,
        int $exeId,
        int $courseId = 0
    ): void {
        if (!empty($courseId)) {
            $this->course = api_get_course_info_by_id($courseId);
        }
        $this->sessionId = $sessionId;
        $this->userId = $userId;
        $this->exerciseId = 0;
        if (!empty($exerciseId)) {
            $this->exerciseId = $exerciseId;
        }
        $this->exeId = $exeId;
        $this->storePath = $this->generateDirectory();
        $this->fileName = $this->generateFileName();
        $this->filePath = $this->storePath.$this->fileName;
    }

    /**
     * Return the HTML code to show the RecordRTC/Wami recorder.
     *
     * @return string
     */
    public function returnRecorder()
    {
        $directory = '/..'.$this->generateRelativeDirectory();
        $recordAudioView = new Template(
            '',
            false,
            false,
            false,
            false,
            false,
            false
        );

        $recordAudioView->assign('directory', $directory);
        $recordAudioView->assign('user_id', $this->userId);
        $recordAudioView->assign('file_name', $this->fileName);
        $recordAudioView->assign('question_id', $this->iid);

        $template = $recordAudioView->get_template('exercise/oral_expression.tpl');

        return $recordAudioView->fetch($template);
    }

    /**
     * Get the absolute file path. Return null if the file doesn't exists.
     *
     * @param bool $loadFromDatabase
     *
     * @return string
     */
    public function getAbsoluteFilePath($loadFromDatabase = false)
    {
        $fileName = $this->fileName;

        if ($loadFromDatabase) {
            $em = Database::getManager();
            //Load the real filename just if exists
            if (isset($this->exeId, $this->userId, $this->iid, $this->sessionId, $this->course['real_id'])) {
                $result = $em
                    ->getRepository('ChamiloCoreBundle:TrackEAttempt')
                    ->findOneBy([
                        'exeId' => $this->exeId,
                        'userId' => $this->userId,
                        'questionId' => $this->iid,
                        'sessionId' => $this->sessionId,
                        'cId' => $this->course['real_id'],
                    ]);

                if (!$result) {
                    return '';
                }

                $fileName = $result->getFilename();

                if (empty($fileName)) {
                    return '';
                }

                return $this->storePath.$result->getFilename();
            }
        }

        foreach ($this->available_extensions as $extension) {
            $audioFile = $this->storePath.$fileName;
            $file = "$audioFile.$extension";

            if (is_file($file)) {
                return $file;
            }

            // Function handle_uploaded_document() adds the session and group id by default.
            $file = "$audioFile".'__'.$this->sessionId."__0.$extension";

            if (is_file($file)) {
                return $file;
            }

            continue;
        }

        return '';
    }

    /**
     * Get the URL for the audio file. Return null if the file doesn't exists.
     *
     * @param bool $loadFromDatabase
     *
     * @return string
     */
    public function getFileUrl($loadFromDatabase = false)
    {
        $filePath = $this->getAbsoluteFilePath($loadFromDatabase);

        if (empty($filePath)) {
            return null;
        }

        return str_replace(
            api_get_path(SYS_COURSE_PATH),
            api_get_path(WEB_COURSE_PATH),
            $filePath
        );
    }

    /**
     * Tricky stuff to deal with the feedback = 0 in exercises (all question per page).
     *
     * @param int $exe_id
     */
    public function replaceWithRealExe($exe_id)
    {
        $filename = null;
        //ugly fix
        foreach ($this->available_extensions as $extension) {
            $items = explode('-', $this->fileName);
            $items[5] = 'temp_exe';
            $filename = implode('-', $items);

            if (is_file($this->storePath.$filename.'.'.$extension)) {
                $old_name = $this->storePath.$filename.'.'.$extension;
                $items = explode('-', $this->fileName);
                $items[5] = $exe_id;
                $filename = $filename = implode('-', $items);
                $new_name = $this->storePath.$filename.'.'.$extension;
                rename($old_name, $new_name);
                break;
            }
        }
    }

    /**
     * Generate the necessary directory for audios. If them not exists, are created.
     *
     * @return string
     */
    private function generateDirectory()
    {
        $this->storePath = api_get_path(SYS_COURSE_PATH).$this->course['path'].'/exercises/';

        if (!is_dir($this->storePath)) {
            mkdir($this->storePath);
        }

        if (!is_dir($this->storePath.$this->sessionId)) {
            mkdir($this->storePath.$this->sessionId);
        }

        if (!empty($this->exerciseId) && !is_dir($this->storePath.$this->sessionId.'/'.$this->exerciseId)) {
            mkdir($this->storePath.$this->sessionId.'/'.$this->exerciseId);
        }

        if (!empty($this->iid) && !is_dir($this->storePath.$this->sessionId.'/'.$this->exerciseId.'/'.$this->iid)) {
            mkdir($this->storePath.$this->sessionId.'/'.$this->exerciseId.'/'.$this->iid);
        }

        if (!empty($this->userId) &&
            !is_dir($this->storePath.$this->sessionId.'/'.$this->exerciseId.'/'.$this->iid.'/'.$this->userId)
        ) {
            mkdir($this->storePath.$this->sessionId.'/'.$this->exerciseId.'/'.$this->iid.'/'.$this->userId);
        }

        $params = [
            $this->sessionId,
            $this->exerciseId,
            $this->iid,
            $this->userId,
        ];

        $this->storePath .= implode('/', $params).'/';

        return $this->storePath;
    }

    /**
     * Generate the file name.
     *
     * @return string
     */
    private function generateFileName()
    {
        return implode(
            '-',
            [
                $this->course['real_id'],
                $this->sessionId,
                $this->userId,
                $this->exerciseId,
                $this->iid,
                $this->exeId,
            ]
        );
    }

    /**
     * Generate a relative directory path.
     *
     * @return string
     */
    private function generateRelativeDirectory()
    {
        $params = [
            $this->sessionId,
            $this->exerciseId,
            $this->iid,
            $this->userId,
        ];

        $path = implode('/', $params);
        $directory = '/exercises/'.$path.'/';

        return $directory;
    }
}
