<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\IntegrationBundle\Component;

/**
 * Class OutcomeUnsupportedResponse.
 *
 * @package Chamilo\IntegrationBundle\Component
 */
class OutcomeUnsupportedResponse extends OutcomeResponse
{
    /**
     * OutcomeUnsupportedResponse constructor.
     *
     * @param OutcomeResponseStatus $statusInfo
     * @param string                      $type
     */
    public function __construct(OutcomeResponseStatus $statusInfo, $type)
    {
        $statusInfo->setOperationRefIdentifier($type);

        parent::__construct($statusInfo);
    }

    /**
     * @param \SimpleXMLElement $xmlBody
     */
    protected function generateBody(\SimpleXMLElement $xmlBody)
    {
    }
}
