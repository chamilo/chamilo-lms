<?php
/* For licensing terms, see /license.txt */

/**
 * Class ImsLtiReplaceServiceResponse.
 */
class ImsLtiServiceReplaceResponse extends ImsLtiServiceResponse
{
    /**
     * ImsLtiServiceReplaceResponse constructor.
     *
     * @param ImsLtiServiceResponseStatus $statusInfo
     * @param mixed|null                  $bodyParam
     */
    public function __construct(ImsLtiServiceResponseStatus $statusInfo, $bodyParam = null)
    {
        $statusInfo->setOperationRefIdentifier('replaceResult');

        parent::__construct($statusInfo, $bodyParam);
    }

    /**
     * @param SimpleXMLElement $xmlBody
     */
    protected function generateBody(SimpleXMLElement $xmlBody)
    {
        $xmlBody->addChild('replaceResultResponse');
    }
}
