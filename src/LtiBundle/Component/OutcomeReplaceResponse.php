<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Component;

/**
 * Class OutcomeReplaceResponse.
 */
class OutcomeReplaceResponse extends OutcomeResponse
{
    /**
     * OutcomeReplaceResponse constructor.
     *
     * @param mixed|null $bodyParam
     */
    public function __construct(OutcomeResponseStatus $statusInfo, $bodyParam = null)
    {
        $statusInfo->setOperationRefIdentifier('replaceResult');

        parent::__construct($statusInfo, $bodyParam);
    }

    protected function generateBody(\SimpleXMLElement $xmlBody)
    {
        $xmlBody->addChild('replaceResultResponse');
    }
}
