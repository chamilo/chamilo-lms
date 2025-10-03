<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\UserBundle\Entity\User;

/**
 * Class ImsLtiServiceReadRequest.
 */
class ImsLtiServiceReadRequest extends ImsLtiServiceRequest
{
    /**
     * ImsLtiServiceReadRequest constructor.
     */
    public function __construct(SimpleXMLElement $xml)
    {
        parent::__construct($xml);

        $this->responseType = ImsLtiServiceResponse::TYPE_READ;
        $this->xmlRequest = $this->xmlRequest->readResultRequest;
    }

    protected function processBody()
    {
        $resultRecord = $this->xmlRequest->resultRecord;
        $sourcedId = (string) $resultRecord->sourcedGUID->sourcedId;
        $sourcedId = htmlspecialchars_decode($sourcedId);

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

        $results = Result::load(null, $user->getId(), $evaluation->getId());

        $ltiScore = '';
        $responseDescription = get_plugin_lang('ScoreNotSet', 'ImsLtiPlugin');

        if (!empty($results)) {
            /** @var Result $result */
            $result = $results[0];
            $ltiScore = 0;

            if (!empty($result->get_score())) {
                $ltiScore = $result->get_score() / $evaluation->getMax();
            }

            $responseDescription = sprintf(
                get_plugin_lang('ScoreForXUserIsYScore', 'ImsLtiPlugin'),
                $user->getId(),
                $ltiScore
            );
        }

        $this->statusInfo
            ->setSeverity(ImsLtiServiceResponseStatus::SEVERITY_STATUS)
            ->setCodeMajor(ImsLtiServiceResponseStatus::CODEMAJOR_SUCCESS)
            ->setDescription($responseDescription);

        $this->responseBodyParam = (string) $ltiScore;
    }
}
