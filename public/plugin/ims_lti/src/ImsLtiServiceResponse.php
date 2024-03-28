<?php
/* For licensing terms, see /license.txt */

/**
 * Class ImsLtiServiceResponse.
 */
abstract class ImsLtiServiceResponse
{
    const TYPE_REPLACE = 'replace';
    const TYPE_READ = 'read';
    const TYPE_DELETE = 'delete';

    /**
     * @var mixed
     */
    protected $bodyParams;
    /**
     * @var ImsLtiServiceResponseStatus
     */
    private $statusInfo;

    /**
     * ImsLtiServiceResponse constructor.
     *
     * @param mixed|null $bodyParam
     */
    public function __construct(ImsLtiServiceResponseStatus $statusInfo, $bodyParam = null)
    {
        $this->statusInfo = $statusInfo;
        $this->bodyParams = $bodyParam;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $xml = new SimpleXMLElement('<imsx_POXEnvelopeResponse></imsx_POXEnvelopeResponse>');
        $xml->addAttribute('xmlns', 'http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0');

        $headerInfo = $xml->addChild('imsx_POXHeader')->addChild('imsx_POXResponseHeaderInfo');
        $headerInfo->addChild('imsx_version', 'V1.0');
        $headerInfo->addChild('imsx_messageIdentifier', time());

        $statusInfo = $headerInfo->addChild('imsx_statusInfo');
        $statusInfo->addChild('imsx_codeMajor', $this->statusInfo->getCodeMajor());
        $statusInfo->addChild('imsx_severity', $this->statusInfo->getSeverity());
        $statusInfo->addChild('imsx_description', $this->statusInfo->getDescription());
        $statusInfo->addChild('imsx_messageRefIdentifier', $this->statusInfo->getMessageRefIdentifier());
        $statusInfo->addChild('imsx_operationRefIdentifier', $this->statusInfo->getOperationRefIdentifier());

        $body = $xml->addChild('imsx_POXBody');

        $this->generateBody($body);

        return $xml->asXML();
    }

    abstract protected function generateBody(SimpleXMLElement $xmlBody);
}
