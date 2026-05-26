<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\AttemptFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
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
    public const RECORDING_TYPE_ATTEMPT  = 1;
    public const RECORDING_TYPE_FEEDBACK = 2;

    public $typePicture = 'audio_question.png';
    public $explanationLangVar = 'Oral expression';
    public $available_extensions = ['wav', 'ogg'];

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
        $header .= '<table class="'.$this->questionTableClass.'">
            <tr>
                <th>'.get_lang('Answer').'</th>
            </tr>';

        return $header;
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

        // Student recording
        $recordAudioView->assign('type', self::RECORDING_TYPE_ATTEMPT);
        $recordAudioView->assign('t_exercise_id', $trackExerciseId);
        $recordAudioView->assign('question_id', $this->id);

        $template = $recordAudioView->get_template('exercise/oral_expression.html.twig');

        return $recordAudioView->fetch($template);
    }

    public static function saveAssetInQuestionAttempt($attemptId): void
    {
        $em = Container::getEntityManager();

        /** @var TrackEAttempt|null $attempt */
        $attempt = $em->find(TrackEAttempt::class, $attemptId);

        if (null === $attempt) {
            return;
        }

        $questionId = (int) $attempt->getQuestionId();
        $assetKey = 'oral_expression_asset_'.$questionId;
        $exeKey   = 'oral_expression_asset_exe_id_'.$questionId;
        $resourceNodeId = ChamiloSession::read($assetKey);
        $resourceNodeExeId = ChamiloSession::read($exeKey);

        if (empty($resourceNodeId)) {
            return;
        }

        // If we stored an exe_id with the recording, only attach it to attempts from the same exe_id.
        // This prevents wrongly attaching an old recording to a new attempt when the user didn't record again.
        if (!empty($resourceNodeExeId)) {
            $trackExercise = $attempt->getTrackEExercise();
            $attemptExeId = (int) $trackExercise->getExeId();

            if ((int) $resourceNodeExeId !== $attemptExeId) {
                // Clear stale keys to keep the session clean and avoid future wrong attachments.
                ChamiloSession::erase($assetKey);
                ChamiloSession::erase($exeKey);

                return;
            }
        }

        /** @var ResourceNodeRepository $resourceNodeRepo */
        $resourceNodeRepo = Container::getResourceNodeRepository();

        /** @var ResourceNode|null $node */
        $node = $resourceNodeRepo->find((int) $resourceNodeId);

        if (null === $node) {
            return;
        }

        // Do NOT erase the session key here.
        // The exercise flow can save the same question multiple times (Save & continue, then End test),
        // and the AJAX save handler can delete/recreate the TrackEAttempt row.
        // Keeping the key allows re-attaching the same ResourceNode after a re-save.

        // Avoid duplicates if the same attempt is saved multiple times without being deleted.
        foreach ($attempt->getAttemptFiles() as $existingAttemptFile) {
            $existingNode = $existingAttemptFile->getResourceNode();
            if (null !== $existingNode && (int) $existingNode->getId() === (int) $resourceNodeId) {
                return;
            }
        }

        $attemptFile = new AttemptFile();
        $attemptFile->setResourceNode($node);
        $attempt->addAttemptFile($attemptFile);

        $em->persist($attemptFile);
        $em->flush();
    }
}
