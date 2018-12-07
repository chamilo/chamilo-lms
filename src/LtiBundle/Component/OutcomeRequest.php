<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Component;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;

/**
 * Class OutcomeRequest.
 *
 * @package Chamilo\LtiBundle\Component
 */
abstract class OutcomeRequest
{
    /**
     * @var string
     */
    protected $responseType;

    /**
     * @var \SimpleXMLElement
     */
    protected $xmlHeaderInfo;

    /**
     * @var \SimpleXMLElement
     */
    protected $xmlRequest;

    /**
     * @var OutcomeResponseStatus
     */
    protected $statusInfo;

    /**
     * @var mixed
     */
    protected $responseBodyParam;

    /**
     * @var EntityManager
     */
    protected $entityManager;
    /**
     * @var Translator
     */
    protected $translator;

    /**
     * OutcomeRequest constructor.
     *
     * @param \SimpleXMLElement $xml
     */
    public function __construct(\SimpleXMLElement $xml)
    {
        $this->statusInfo = new OutcomeResponseStatus();

        $this->xmlHeaderInfo = $xml->imsx_POXHeader->imsx_POXRequestHeaderInfo;
        $this->xmlRequest = $xml->imsx_POXBody->children();
    }

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Translator $translator
     */
    public function setTranslator(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return OutcomeResponse|null
     */
    public function process()
    {
        $this->processHeader();
        $this->processBody();

        switch ($this->responseType) {
            case OutcomeResponse::TYPE_REPLACE:
                return new OutcomeReplaceResponse($this->statusInfo, $this->responseBodyParam);
            case OutcomeResponse::TYPE_READ:
                return new OutcomeReadResponse($this->statusInfo, $this->responseBodyParam);
            case OutcomeResponse::TYPE_DELETE:
                return new OutcomeDeleteResponse($this->statusInfo, $this->responseBodyParam);
            default:
                return new OutcomeUnsupportedResponse($this->statusInfo, $this->responseBodyParam);
        }
    }

    protected function processHeader()
    {
        $info = $this->xmlHeaderInfo;

        $this->statusInfo->setMessageRefIdentifier($info->imsx_messageIdentifier);

        error_log("Service Request: tool version {$info->imsx_version} message ID {$info->imsx_messageIdentifier}");
    }

    abstract protected function processBody();
}
