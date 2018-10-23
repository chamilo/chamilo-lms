<?php
/* For licensing terms, see /license.txt */

/**
 * Class ImsLtiReadServiceResponse
 */
class ImsLtiServiceReadResponse extends ImsLtiServiceResponse
{
    /**
     * @param SimpleXMLElement $xmlBody
     */
    protected function generateBody(SimpleXMLElement $xmlBody)
    {
        $resultResponse = $xmlBody->addChild('readResultResponse');

        $xmlResultScore = $resultResponse->addChild('result')
            ->addChild('resultScore');

        $xmlResultScore->addChild('language', 'en');
        $xmlResultScore->addChild('textString', $this->bodyParams);
    }
}
