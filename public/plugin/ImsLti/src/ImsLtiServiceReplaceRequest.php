<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\UserBundle\Entity\User;

/**
 * Class ImsLtiReplaceServiceRequest.
 */
class ImsLtiServiceReplaceRequest extends ImsLtiServiceRequest
{
    /**
     * ImsLtiReplaceServiceRequest constructor.
     */
    public function __construct(SimpleXMLElement $xml)
    {
        parent::__construct($xml);

        $this->responseType = ImsLtiServiceResponse::TYPE_REPLACE;
        $this->xmlRequest = $this->xmlRequest->replaceResultRequest;
    }

    protected function processBody()
    {
        $resultRecord = $this->xmlRequest->resultRecord;
        $sourcedId = (string) $resultRecord->sourcedGUID->sourcedId;
        $sourcedId = htmlspecialchars_decode($sourcedId);
        $resultScore = (string) $resultRecord->result->resultScore->textString;

        if (!is_numeric($resultScore)) {
            $this->statusInfo
                ->setSeverity(ImsLtiServiceResponseStatus::SEVERITY_ERROR)
                ->setCodeMajor(ImsLtiServiceResponseStatus::CODEMAJOR_FAILURE);

            return;
        }

        $resultScore = (float) $resultScore;

        if (0 > $resultScore || 1 < $resultScore) {
            $this->statusInfo
                ->setSeverity(ImsLtiServiceResponseStatus::SEVERITY_WARNING)
                ->setCodeMajor(ImsLtiServiceResponseStatus::CODEMAJOR_FAILURE);

            return;
        }

        $sourcedParts = json_decode($sourcedId, true);

        if (empty($sourcedParts)) {
            $this->statusInfo
                ->setSeverity(ImsLtiServiceResponseStatus::SEVERITY_ERROR)
                ->setCodeMajor(ImsLtiServiceResponseStatus::CODEMAJOR_FAILURE);

            return;
        }

        $em = Database::getManager();
        /** @var GradebookEvaluation $evaluation */
        $evaluation = $em->find('ChamiloCoreBundle:GradebookEvaluation', $sourcedParts['e']);
        /** @var User $user */
        $user = $em->find('ChamiloUserBundle:User', $sourcedParts['u']);

        if (empty($evaluation) || empty($user)) {
            $this->statusInfo
                ->setSeverity(ImsLtiServiceResponseStatus::SEVERITY_STATUS)
                ->setCodeMajor(ImsLtiServiceResponseStatus::CODEMAJOR_FAILURE);

            return;
        }

        $score = $evaluation->getMax() * $resultScore;

        $results = Result::load(null, $user->getId(), $evaluation->getId());

        if (empty($results)) {
            $result = new Result();
            $result->set_evaluation_id($evaluation->getId());
            $result->set_user_id($user->getId());
            $result->set_score($score);
            $result->add();
        } else {
            /** @var Result $result */
            $result = $results[0];
            $result->addResultLog($user->getId(), $evaluation->getId());
            $result->set_score($score);
            $result->save();
        }

        $this->statusInfo
            ->setSeverity(ImsLtiServiceResponseStatus::SEVERITY_STATUS)
            ->setCodeMajor(ImsLtiServiceResponseStatus::CODEMAJOR_SUCCESS)
            ->setDescription(
                sprintf(
                    get_plugin_lang('ScoreForXUserIsYScore', 'ImsLtiPlugin'),
                    $user->getId(),
                    $resultScore
                )
            );
    }
}
