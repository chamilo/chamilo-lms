<?php
/* For licensing terms, see /license.txt */

/**
 * Class ImsLtiServiceResponseFactory.
 */
class ImsLtiServiceResponseFactory
{
    /**
     * @param string $type
     * @param mixed  $bodyParam
     *
     * @return ImsLtiServiceResponse|null
     */
    public static function create($type, ImsLtiServiceResponseStatus $statusInfo, $bodyParam = null)
    {
        switch ($type) {
            case ImsLtiServiceResponse::TYPE_REPLACE:
                return new ImsLtiServiceReplaceResponse($statusInfo, $bodyParam);
            case ImsLtiServiceResponse::TYPE_READ:
                return new ImsLtiServiceReadResponse($statusInfo, $bodyParam);
            case ImsLtiServiceResponse::TYPE_DELETE:
                return new ImsLtiServiceDeleteResponse($statusInfo, $bodyParam);
            default:
                return new ImsLtiServiceUnsupportedResponse($statusInfo, $type);
        }

        return null;
    }
}
