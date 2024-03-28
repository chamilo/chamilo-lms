<?php
/* For licensing terms, see /license.txt */

/**
 * Class ImsLtiServiceUnsupportedRequest.
 */
class ImsLtiServiceUnsupportedRequest extends ImsLtiServiceRequest
{
    /**
     * ImsLtiDeleteServiceRequest constructor.
     *
     * @param string $name
     */
    public function __construct(SimpleXMLElement $xml, $name)
    {
        parent::__construct($xml);

        $this->responseType = $name;
    }

    protected function processBody()
    {
        $this->statusInfo
            ->setSeverity(ImsLtiServiceResponseStatus::SEVERITY_STATUS)
            ->setCodeMajor(ImsLtiServiceResponseStatus::CODEMAJOR_UNSUPPORTED)
            ->setDescription(
                $this->responseType.' is not supported'
            );
    }
}
