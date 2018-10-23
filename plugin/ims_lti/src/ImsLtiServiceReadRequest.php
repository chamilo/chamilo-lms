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
     *
     * @param SimpleXMLElement $xml
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

        $sourcedParts = explode(':', $sourcedId);

        $em = Database::getManager();
        /** @var GradebookEvaluation $evaluation */
        $evaluation = $em->find('ChamiloCoreBundle:GradebookEvaluation', $sourcedParts[0]);
        /** @var User $user */
        $user = $em->find('ChamiloUserBundle:User', $sourcedParts[1]);

        if (empty($evaluation) || empty($user)) {
            $this->statusInfo
                ->setSeverity(ImsLtiServiceResponseStatus::SEVERITY_STATUS)
                ->setCodeMajor(ImsLtiServiceResponseStatus::CODEMAJOR_FAILURE);

            return;
        }

        $result = new Result();
        $result->set_evaluation_id($evaluation->getId());
        $result->set_user_id($user->getId());

        if (!$result->exists()) {
            $this->statusInfo
                ->setSeverity(ImsLtiServiceResponseStatus::SEVERITY_STATUS)
                ->setCodeMajor(ImsLtiServiceResponseStatus::CODEMAJOR_FAILURE);

            return;
        }

        $results = Result::load(null, $user->getId(), $evaluation->getId());

        $ltiScore = '';

        if (!empty($results)) {
            /** @var Result $result */
            $result = $results[0];

            $ltiScore = $evaluation->getMax() / $result->get_score() * 10;
        }

        $this->statusInfo
            ->setSeverity(ImsLtiServiceResponseStatus::SEVERITY_STATUS)
            ->setCodeMajor(ImsLtiServiceResponseStatus::CODEMAJOR_SUCCESS)
            ->setDescription(
                get_plugin_lang('ResultRead', 'ImsLtiPlugin')
            );

        $this->responseBodyParam = (string) $ltiScore;
    }
}
