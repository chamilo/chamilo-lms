<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Component;

/**
 * Class OutcomeUnsupportedResponse.
 */
class OutcomeUnsupportedResponse extends OutcomeResponse
{
    /**
     * OutcomeUnsupportedResponse constructor.
     *
     * @param string $type
     */
    public function __construct(OutcomeResponseStatus $statusInfo, $type)
    {
        $statusInfo->setOperationRefIdentifier($type);

        parent::__construct($statusInfo);
    }

    protected function generateBody(\SimpleXMLElement $xmlBody)
    {
    }
}
