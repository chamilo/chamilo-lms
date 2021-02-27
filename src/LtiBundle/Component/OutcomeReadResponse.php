<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Component;

use SimpleXMLElement;

class OutcomeReadResponse extends OutcomeResponse
{
    /**
     * OutcomeReadResponse constructor.
     *
     * @param null|mixed $bodyParam
     */
    public function __construct(OutcomeResponseStatus $statusInfo, $bodyParam = null)
    {
        $statusInfo->setOperationRefIdentifier('readResult');

        parent::__construct($statusInfo, $bodyParam);
    }

    protected function generateBody(SimpleXMLElement $xmlBody): void
    {
        $resultResponse = $xmlBody->addChild('readResultResponse');

        $xmlResultScore = $resultResponse->addChild('result')
            ->addChild('resultScore')
        ;

        $xmlResultScore->addChild('language', 'en');
        $xmlResultScore->addChild('textString', $this->bodyParams);
    }
}
