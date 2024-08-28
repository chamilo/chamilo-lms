<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Component;

use SimpleXMLElement;

class OutcomeUnsupportedResponse extends OutcomeResponse
{
    /**
     * @param string $type
     */
    public function __construct(OutcomeResponseStatus $statusInfo, $type)
    {
        $statusInfo->setOperationRefIdentifier($type);

        parent::__construct($statusInfo);
    }

    protected function generateBody(SimpleXMLElement $xmlBody): void {}
}
