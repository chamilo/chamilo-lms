<?php
/* For licensing terms, see /license.txt */

/**
 * Class ImsLtiReadServiceResponse.
 */
class ImsLtiServiceReadResponse extends ImsLtiServiceResponse
{
    /**
     * ImsLtiServiceReadResponse constructor.
     *
     * @param mixed|null $bodyParam
     */
    public function __construct(ImsLtiServiceResponseStatus $statusInfo, $bodyParam = null)
    {
        $statusInfo->setOperationRefIdentifier('readResult');

        parent::__construct($statusInfo, $bodyParam);
    }

    protected function generateBody(SimpleXMLElement $xmlBody)
    {
        $resultResponse = $xmlBody->addChild('readResultResponse');

        $xmlResultScore = $resultResponse->addChild('result')
            ->addChild('resultScore');

        $xmlResultScore->addChild('language', 'en');
        $xmlResultScore->addChild('textString', $this->bodyParams);
    }
}
