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
     * @param mixed|null $bodyParam
     */
    public function __construct(ImsLtiServiceResponseStatus $statusInfo, $bodyParam = null)
    {
        $statusInfo->setOperationRefIdentifier('replaceResult');

        parent::__construct($statusInfo, $bodyParam);
    }

    protected function generateBody(SimpleXMLElement $xmlBody)
    {
        $xmlBody->addChild('replaceResultResponse');
    }
}
