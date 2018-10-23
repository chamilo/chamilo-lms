<?php
/* For licensing terms, see /license.txt */

/**
 * Class ImsLtiServiceRequestFactory.
 */
class ImsLtiServiceRequestFactory
{
    /**
     * @param SimpleXMLElement $xml
     *
     * @return ImsLtiServiceRequest|null
     */
    public static function create(SimpleXMLElement $xml)
    {
        $bodyChildren = $xml->imsx_POXBody->children();

        if (!empty($bodyChildren)) {
            switch ($bodyChildren->getName()) {
                case 'replaceResultRequest':
                    return new ImsLtiServiceReplaceRequest($xml);
                case 'readResultRequest':
                    return new ImsLtiServiceReadRequest($xml);
                case 'deleteResultRequest':
                    return new ImsLtiServiceDeleteRequest($xml);
            }
        }

        return null;
    }
}
