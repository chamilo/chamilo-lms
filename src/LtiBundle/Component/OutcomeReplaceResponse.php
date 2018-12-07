<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Component;

/**
 * Class OutcomeReplaceResponse.
 *
 * @package Chamilo\LtiBundle\Component
 */
class OutcomeReplaceResponse extends OutcomeResponse
{
    /**
     * OutcomeReplaceResponse constructor.
     *
     * @param OutcomeResponseStatus $statusInfo
     * @param mixed|null            $bodyParam
     */
    public function __construct(OutcomeResponseStatus $statusInfo, $bodyParam = null)
    {
        $statusInfo->setOperationRefIdentifier('replaceResult');

        parent::__construct($statusInfo, $bodyParam);
    }

    /**
     * @param \SimpleXMLElement $xmlBody
     */
    protected function generateBody(\SimpleXMLElement $xmlBody)
    {
        $xmlBody->addChild('replaceResultResponse');
    }
}
