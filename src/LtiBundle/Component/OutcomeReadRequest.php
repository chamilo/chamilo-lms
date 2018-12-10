<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Component;

use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\UserBundle\Entity\User;

/**
 * Class OutcomeReadRequest.
 *
 * @package Chamilo\LtiBundle\Component
 */
class OutcomeReadRequest extends OutcomeRequest
{
    /**
     * OutcomeReadRequest constructor.
     *
     * @param \SimpleXMLElement $xml
     */
    public function __construct(\SimpleXMLElement $xml)
    {
        parent::__construct($xml);

        $this->responseType = OutcomeResponse::TYPE_READ;
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
                ->setSeverity(OutcomeResponseStatus::SEVERITY_ERROR)
                ->setCodeMajor(OutcomeResponseStatus::CODEMAJOR_FAILURE);

            return;
        }

        /** @var GradebookEvaluation $evaluation */
        $evaluation = $this->entityManager->find('ChamiloCoreBundle:GradebookEvaluation', $sourcedParts['e']);
        /** @var User $user */
        $user = $this->entityManager->find('ChamiloUserBundle:User', $sourcedParts['u']);

        if (empty($evaluation) || empty($user)) {
            $this->statusInfo
                ->setSeverity(OutcomeResponseStatus::SEVERITY_STATUS)
                ->setCodeMajor(OutcomeResponseStatus::CODEMAJOR_FAILURE);

            return;
        }

        $results = \Result::load(null, $user->getId(), $evaluation->getId());

        $ltiScore = '';
        $responseDescription = $this->translator->trans('Score not set');

        if (!empty($results)) {
            /** @var \Result $result */
            $result = $results[0];
            $ltiScore = 0;

            if (!empty($result->get_score())) {
                $ltiScore = $result->get_score() / $evaluation->getMax();
            }

            $responseDescription = sprintf(
                $this->translator->trans('Score for user %d is %s'),
                $user->getId(),
                $ltiScore
            );
        }

        $this->statusInfo
            ->setSeverity(OutcomeResponseStatus::SEVERITY_STATUS)
            ->setCodeMajor(OutcomeResponseStatus::CODEMAJOR_SUCCESS)
            ->setDescription($responseDescription);

        $this->responseBodyParam = (string) $ltiScore;
    }
}
