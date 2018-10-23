<?php
/* For licensing terms, see /license.txt */

/**
 * Class ImsLtiServiceDeleteResponse.
 */
class ImsLtiServiceDeleteResponse extends ImsLtiServiceResponse
{
    /**
     * @param SimpleXMLElement $xmlBody
     */
    protected function generateBody(SimpleXMLElement $xmlBody)
    {
        $xmlBody->addChild('deleteResultResponse');
    }
}
