<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\IntegrationBundle\Component;

/**
 * Class OutcomeUnsupportedRequest.
 *
 * @package Chamilo\IntegrationBundle\Component
 */
class OutcomeUnsupportedRequest extends OutcomeRequest
{
    /**
     * OutcomeUnsupportedRequest constructor.
     *
     * @param \SimpleXMLElement $xml
     * @param string            $name
     */
    public function __construct(\SimpleXMLElement $xml, $name)
    {
        parent::__construct($xml);

        $this->responseType = $name;
    }

    protected function processBody()
    {
        $this->statusInfo
            ->setSeverity(OutcomeResponseStatus::SEVERITY_STATUS)
            ->setCodeMajor(OutcomeResponseStatus::CODEMAJOR_UNSUPPORTED)
            ->setDescription(
                $this->responseType.' is not supported'
            );
    }
}
