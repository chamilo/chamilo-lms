<?php
/* For licensing terms, see /license.txt */

/**
 * Class ImsLtiServiceRequestFactory.
 */
class ImsLtiServiceRequestFactory
{
    /**
     * @return ImsLtiServiceRequest|null
     */
    public static function create(SimpleXMLElement $xml)
    {
        $bodyChildren = $xml->imsx_POXBody->children();

        if (!empty($bodyChildren)) {
            $name = $bodyChildren->getName();

            switch ($name) {
                case 'replaceResultRequest':
                    return new ImsLtiServiceReplaceRequest($xml);
                case 'readResultRequest':
                    return new ImsLtiServiceReadRequest($xml);
                case 'deleteResultRequest':
                    return new ImsLtiServiceDeleteRequest($xml);
                default:
                    $name = str_replace(['ResultRequest', 'Request'], '', $name);

                    return new ImsLtiServiceUnsupportedRequest($xml, $name);
            }
        }

        return null;
    }
}
