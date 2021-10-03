<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\AttemptFile;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\Uid\Uuid;

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
    public $explanationLangVar = 'Oral expression';
    public $available_extensions = ['wav', 'ogg'];
    private $sessionId;
    private $userId;
    private $exerciseId;
    private $exeId;
    private $storePath;
    private $fileName;
    private $filePath;

    public function __construct()
    {
        parent::__construct();
        $this->type = ORAL_EXPRESSION;
        $this->isContent = $this->getIsContent();
    }

    public function createAnswersForm($form)
    {
        $form->addText(
            'weighting',
            get_lang('Score'),
            ['class' => 'span1']
        );
        global $text;
        // setting the save button here and not in the question class.php
        $form->addButtonSave($text, 'submitQuestion');
        if (!empty($this->id)) {
            $form->setDefaults(['weighting' => float_format($this->weighting, 1)]);
        } else {
            if (1 == $this->isContent) {
                $form->setDefaults(['weighting' => '10']);
            }
        }
    }

    public function processAnswersCreation($form, $exercise)
    {
        $this->weighting = $form->getSubmitValue('weighting');
        $this->save($exercise);
    }

    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $score['revised'] = $this->isQuestionWaitingReview($score);
        $header = parent::return_header($exercise, $counter, $score);
        $header .= '<table class="'.$this->question_table_class.'">
            <tr>
                <th>'.get_lang('Answer').'</th>
            </tr>';

        return $header;
    }

    /**
     * initialize the attributes to generate the file path.
     *
     * @param int $sessionId
     * @param int $userId
     * @param int $exerciseId
     * @param int $exeId
     */
    public function initFile($sessionId, $userId, $exerciseId, $exeId)
    {
        $this->sessionId = (int) $sessionId;
        $this->userId = (int) $userId;
        $this->exerciseId = 0;
        $this->exeId = (int) $exeId;
        if (!empty($exerciseId)) {
            $this->exerciseId = (int) $exerciseId;
        }
    }

    /**
     * Return the HTML code to show the RecordRTC/Wami recorder.
     */
    public function returnRecorder(int $trackExerciseId): string
    {
        $recordAudioView = new Template(
            '',
            false,
            false,
            false,
            false,
            false,
            false
        );

        $recordAudioView->assign('type', Asset::EXERCISE_ATTEMPT);
        $recordAudioView->assign('t_exercise_id', $trackExerciseId);
        $recordAudioView->assign('question_id', $this->id);

        $template = $recordAudioView->get_template('exercise/oral_expression.html.twig');

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
            if (isset($this->exeId, $this->userId, $this->id, $this->sessionId, $this->course['real_id'])) {
                $result = $em
                    ->getRepository(TrackEAttempt::class)
                    ->findOneBy([
                        'exeId' => $this->exeId,
                        'userId' => $this->userId,
                        'questionId' => $this->id,
                        'sessionId' => $this->sessionId,
                        'course' => $this->course['real_id'],
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
     * @todo fix path
     *
     * @param bool $loadFromDatabase
     *
     * @return string
     */
    public function getFileUrl($loadFromDatabase = false)
    {
        return null;

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

    public static function saveAssetInQuestionAttempt($attemptId)
    {
        $em = Container::getEntityManager();

        $attempt = $em->find(TrackEAttempt::class, $attemptId);

        $variable = 'oral_expression_asset_'.$attempt->getQuestionId();

        $assetId = ChamiloSession::read($variable);
        $asset = Container::getAssetRepository()->find(Uuid::fromRfc4122($assetId));

        if (null === $asset) {
            return;
        }

        ChamiloSession::erase($variable);

        $attemptFile = (new AttemptFile())
            ->setAsset($asset)
        ;

        $attempt->addAttemptFile($attemptFile);

        $em->persist($attemptFile);
        $em->flush();
    }
}
