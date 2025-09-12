<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\AttemptFile;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\Uid\Uuid;

/**
 * Question with file upload, where the file is the answer.
 * Acts as an open question: requires teacher's review for a score.
 */
class UploadAnswer extends Question
{
    public $typePicture = 'file_upload_question.png';
    public $explanationLangVar = 'Upload Answer';

    public function __construct()
    {
        parent::__construct();
        $this->type = UPLOAD_ANSWER;
        $this->isContent = $this->getIsContent();
    }

    /** {@inheritdoc} */
    public function createAnswersForm($form)
    {
        $form->addElement('text', 'weighting', get_lang('Score'));
        global $text;
        // Set the save button here (not in question.class.php)
        $form->addButtonSave($text, 'submitQuestion');

        if (!empty($this->iid)) {
            $form->setDefaults(['weighting' => float_format($this->weighting, 1)]);
        } elseif (1 == $this->isContent) {
            $form->setDefaults(['weighting' => '10']);
        }
    }

    /** {@inheritdoc} */
    public function processAnswersCreation($form, $exercise)
    {
        $this->weighting = $form->getSubmitValue('weighting');
        $this->save($exercise);
    }

    /** {@inheritdoc} */
    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $score['revised'] = $this->isQuestionWaitingReview($score);
        $header = parent::return_header($exercise, $counter, $score);

        $tableClass = property_exists($this, 'questionTableClass')
            ? $this->questionTableClass
            : ($this->question_table_class ?? 'data_table');

        $header .= '<table class="'.$tableClass.'">
            <tr>
                <th>'.get_lang('Answer').'</th>
            </tr>';

        return $header;
    }

    /**
     * Attach uploaded Asset(s) to the question attempt as AttemptFile.
     */
    public static function saveAssetInQuestionAttempt(int $attemptId, array $postedAssetIds = []): void
    {
        $em = Container::getEntityManager();

        /** @var TrackEAttempt|null $attempt */
        $attempt = $em->find(TrackEAttempt::class, $attemptId);
        if (null === $attempt) {
            return;
        }

        $questionId = (int) $attempt->getQuestionId();
        $sessionKey = 'upload_answer_assets_'.$questionId;

        $assetIds = array_values(array_filter(array_map('strval', $postedAssetIds)));
        if (empty($assetIds)) {
            $sessionVal = ChamiloSession::read($sessionKey);
            $assetIds = is_array($sessionVal) ? $sessionVal : (empty($sessionVal) ? [] : [$sessionVal]);
        }
        if (empty($assetIds)) {
            return;
        }

        ChamiloSession::erase($sessionKey);
        $repo = Container::getAssetRepository();

        foreach ($assetIds as $id) {
            try {
                $asset = $repo->find(Uuid::fromRfc4122($id));
            } catch (\Throwable $e) {
                continue;
            }
            if (!$asset) {
                continue;
            }

            $attemptFile = (new AttemptFile())->setAsset($asset);
            $attempt->addAttemptFile($attemptFile);
            $em->persist($attemptFile);
        }

        $em->flush();
    }
}
