<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Component;

/**
 * Class OutcomeReadResponse.
 *
 * @package Chamilo\LtiBundle\Component
 */
class OutcomeReadResponse extends OutcomeResponse
{
    /**
     * OutcomeReadResponse constructor.
     *
     * @param OutcomeResponseStatus $statusInfo
     * @param mixed|null            $bodyParam
     */
    public function __construct(OutcomeResponseStatus $statusInfo, $bodyParam = null)
    {
        $statusInfo->setOperationRefIdentifier('readResult');

        parent::__construct($statusInfo, $bodyParam);
    }

    /**
     * @param \SimpleXMLElement $xmlBody
     */
    protected function generateBody(\SimpleXMLElement $xmlBody)
    {
        $resultResponse = $xmlBody->addChild('readResultResponse');

        $xmlResultScore = $resultResponse->addChild('result')
            ->addChild('resultScore');

        $xmlResultScore->addChild('language', 'en');
        $xmlResultScore->addChild('textString', $this->bodyParams);
    }
}
