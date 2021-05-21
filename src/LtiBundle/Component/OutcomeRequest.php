<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Component;

use Doctrine\ORM\EntityManager;
use \SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;

abstract class OutcomeRequest
{
    protected string $responseType;

    protected SimpleXMLElement $xmlHeaderInfo;

    protected SimpleXMLElement $xmlRequest;

    protected OutcomeResponseStatus $statusInfo;

    protected array $responseBodyParam;

    protected EntityManager $entityManager;
    protected Translator $translator;

    public function __construct(SimpleXMLElement $xml)
    {
        $this->statusInfo = new OutcomeResponseStatus();

        $this->xmlHeaderInfo = $xml->imsx_POXHeader->imsx_POXRequestHeaderInfo;
        $this->xmlRequest = $xml->imsx_POXBody->children();
    }

    public function setEntityManager(EntityManager $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    public function setTranslator(Translator $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * @return null|OutcomeResponse
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

    protected function processHeader(): void
    {
        $info = $this->xmlHeaderInfo;

        $this->statusInfo->setMessageRefIdentifier($info->imsx_messageIdentifier);

        error_log("Service Request: tool version {$info->imsx_version} message ID {$info->imsx_messageIdentifier}");
    }

    abstract protected function processBody();
}
