<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\UserBundle\Entity\User;

/**
 * Class ImsLtiDeleteServiceRequest.
 */
class ImsLtiServiceDeleteRequest extends ImsLtiServiceRequest
{
    /**
     * ImsLtiDeleteServiceRequest constructor.
     *
     * @param SimpleXMLElement $xml
     */
    public function __construct(SimpleXMLElement $xml)
    {
        parent::__construct($xml);

        $this->responseType = ImsLtiServiceResponse::TYPE_DELETE;
        $this->xmlRequest = $this->xmlRequest->deleteResultRequest;
    }

    protected function processBody()
    {
        $resultRecord = $this->body->replaceResultRequest->resultRecord;
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

        $result->addResultLog($user->getId(), $evaluation->getId());
        $result->delete();

        $this->statusInfo
            ->setSeverity(ImsLtiServiceResponseStatus::SEVERITY_STATUS)
            ->setCodeMajor(ImsLtiServiceResponseStatus::CODEMAJOR_SUCCESS);
    }
}
