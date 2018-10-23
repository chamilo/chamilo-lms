<?php
/* For licensing terms, see /license.txt */

/**
 * Class ImsLtiReplaceServiceResponse.
 */
class ImsLtiServiceReplaceResponse extends ImsLtiServiceResponse
{
    /**
     * @param SimpleXMLElement $xmlBody
     */
    protected function generateBody(SimpleXMLElement $xmlBody)
    {
        $xmlBody->addChild('replaceResultResponse');
    }
}
