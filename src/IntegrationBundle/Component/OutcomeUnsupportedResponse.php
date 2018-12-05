<?php
/* For licensing terms, see /license.txt */

/**
 * Class ImsLtiServiceUnsupportedResponse.
 */
class ImsLtiServiceUnsupportedResponse extends ImsLtiServiceResponse
{
    /**
     * ImsLtiServiceUnsupportedResponse constructor.
     *
     * @param ImsLtiServiceResponseStatus $statusInfo
     * @param string                      $type
     */
    public function __construct(ImsLtiServiceResponseStatus $statusInfo, $type)
    {
        $statusInfo->setOperationRefIdentifier($type);

        parent::__construct($statusInfo);
    }

    /**
     * @param SimpleXMLElement $xmlBody
     */
    protected function generateBody(SimpleXMLElement $xmlBody)
    {
    }
}
