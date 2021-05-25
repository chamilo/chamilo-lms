<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Component;

use SimpleXMLElement;

class OutcomeUnsupportedRequest extends OutcomeRequest
{
    /**
     * OutcomeUnsupportedRequest constructor.
     *
     * @param string $name
     */
    public function __construct(SimpleXMLElement $xml, $name)
    {
        parent::__construct($xml);

        $this->responseType = $name;
    }

    protected function processBody(): void
    {
        $this->statusInfo
            ->setSeverity(OutcomeResponseStatus::SEVERITY_STATUS)
            ->setCodeMajor(OutcomeResponseStatus::CODEMAJOR_UNSUPPORTED)
            ->setDescription(
                $this->responseType.' is not supported'
            )
        ;
    }
}
