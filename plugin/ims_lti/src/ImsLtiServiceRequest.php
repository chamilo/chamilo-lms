<?php
/* For licensing terms, see /license.txt */

/**
 * Class ImsLtiServiceRequest.
 */
abstract class ImsLtiServiceRequest
{
    /**
     * @var string
     */
    protected $responseType;

    /**
     * @var SimpleXMLElement
     */
    protected $xmlHeaderInfo;

    /**
     * @var SimpleXMLElement
     */
    protected $xmlRequest;

    /**
     * @var ImsLtiServiceResponseStatus
     */
    protected $statusInfo;

    /**
     * @var mixed
     */
    protected $responseBodyParam;

    /**
     * ImsLtiServiceRequest constructor.
     */
    public function __construct(SimpleXMLElement $xml)
    {
        $this->statusInfo = new ImsLtiServiceResponseStatus();

        $this->xmlHeaderInfo = $xml->imsx_POXHeader->imsx_POXRequestHeaderInfo;
        $this->xmlRequest = $xml->imsx_POXBody->children();
    }

    /**
     * @return ImsLtiServiceResponse|null
     */
    public function process()
    {
        $this->processHeader();
        $this->processBody();

        return $this->generateResponse();
    }

    protected function processHeader()
    {
        $info = $this->xmlHeaderInfo;

        $this->statusInfo->setMessageRefIdentifier($info->imsx_messageIdentifier);

        error_log("Service Request: tool version {$info->imsx_version} message ID {$info->imsx_messageIdentifier}");
    }

    abstract protected function processBody();

    /**
     * @return ImsLtiServiceResponse|null
     */
    private function generateResponse()
    {
        $response = ImsLtiServiceResponseFactory::create(
            $this->responseType,
            $this->statusInfo,
            $this->responseBodyParam
        );

        return $response;
    }
}
