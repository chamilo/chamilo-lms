<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Component;

/**
 * Class OutcomeDeleteResponse.
 */
class OutcomeDeleteResponse extends OutcomeResponse
{
    /**
     * OutcomeDeleteResponse constructor.
     *
     * @param mixed|null $bodyParam
     */
    public function __construct(OutcomeResponseStatus $statusInfo, $bodyParam = null)
    {
        $statusInfo->setOperationRefIdentifier('deleteResult');

        parent::__construct($statusInfo, $bodyParam);
    }

    protected function generateBody(\SimpleXMLElement $xmlBody)
    {
        $xmlBody->addChild('deleteResultResponse');
    }
}
