<?php

namespace SAML2;

use Webmozart\Assert\Assert;

/**
 * Base class for all SAML 2 response messages.
 *
 * Implements samlp:StatusResponseType. All of the elements in that type is
 * stored in the \SAML2\Message class, and this class is therefore more
 * or less empty. It is included mainly to make it easy to separate requests from
 * responses.
 *
 * The status code is represented as an array on the following form:
 * [
 *   'Code' => '<top-level status code>',
 *   'SubCode' => '<second-level status code>',
 *   'Message' => '<status message>',
 * ]
 *
 * Only the 'Code' field is required. The others will be set to null if they
 * aren't present.
 *
 * @package SimpleSAMLphp
 */
abstract class StatusResponse extends Message
{
    /**
     * The ID of the request this is a response to, or null if this is an unsolicited response.
     *
     * @var string|null
     */
    private $inResponseTo;


    /**
     * The status code of the response.
     *
     * @var array
     */
    private $status;


    /**
     * Constructor for SAML 2 response messages.
     *
     * @param string          $tagName The tag name of the root element.
     * @param \DOMElement|null $xml     The input message.
     * @throws \Exception
     */
    protected function __construct($tagName, \DOMElement $xml = null)
    {
        parent::__construct($tagName, $xml);

        $this->status = [
            'Code' => Constants::STATUS_SUCCESS,
            'SubCode' => null,
            'Message' => null,
        ];

        if ($xml === null) {
            return;
        }

        if ($xml->hasAttribute('InResponseTo')) {
            $this->inResponseTo = $xml->getAttribute('InResponseTo');
        }

        $status = Utils::xpQuery($xml, './saml_protocol:Status');
        if (empty($status)) {
            throw new \Exception('Missing status code on response.');
        }
        $status = $status[0];

        $statusCode = Utils::xpQuery($status, './saml_protocol:StatusCode');
        if (empty($statusCode)) {
            throw new \Exception('Missing status code in status element.');
        }
        $statusCode = $statusCode[0];

        $this->status['Code'] = $statusCode->getAttribute('Value');

        $subCode = Utils::xpQuery($statusCode, './saml_protocol:StatusCode');
        if (!empty($subCode)) {
            $this->status['SubCode'] = $subCode[0]->getAttribute('Value');
        }

        $message = Utils::xpQuery($status, './saml_protocol:StatusMessage');
        if (!empty($message)) {
            $this->status['Message'] = trim($message[0]->textContent);
        }
    }


    /**
     * Determine whether this is a successful response.
     *
     * @return boolean true if the status code is success, false if not.
     */
    public function isSuccess()
    {
        Assert::keyExists($this->status, "Code");

        if ($this->status['Code'] === Constants::STATUS_SUCCESS) {
            return true;
        }

        return false;
    }


    /**
     * Retrieve the ID of the request this is a response to.
     *
     * @return string|null The ID of the request.
     */
    public function getInResponseTo()
    {
        return $this->inResponseTo;
    }


    /**
     * Set the ID of the request this is a response to.
     *
     * @param string|null $inResponseTo The ID of the request.
     * @return void
     */
    public function setInResponseTo($inResponseTo)
    {
        Assert::nullOrString($inResponseTo);

        $this->inResponseTo = $inResponseTo;
    }


    /**
     * Retrieve the status code.
     *
     * @return array The status code.
     */
    public function getStatus()
    {
        return $this->status;
    }


    /**
     * Set the status code.
     *
     * @param array $status The status code.
     * @return void
     */
    public function setStatus(array $status)
    {
        Assert::keyExists($status, "Code");

        $this->status = $status;
        if (!array_key_exists('SubCode', $status)) {
            $this->status['SubCode'] = null;
        }
        if (!array_key_exists('Message', $status)) {
            $this->status['Message'] = null;
        }
    }


    /**
     * Convert status response message to an XML element.
     *
     * @return \DOMElement This status response.
     */
    public function toUnsignedXML()
    {
        $root = parent::toUnsignedXML();

        if ($this->inResponseTo !== null) {
            $root->setAttribute('InResponseTo', $this->inResponseTo);
        }

        $status = $this->document->createElementNS(Constants::NS_SAMLP, 'Status');
        $root->appendChild($status);

        $statusCode = $this->document->createElementNS(Constants::NS_SAMLP, 'StatusCode');
        $statusCode->setAttribute('Value', $this->status['Code']);
        $status->appendChild($statusCode);

        if (!is_null($this->status['SubCode'])) {
            $subStatusCode = $this->document->createElementNS(Constants::NS_SAMLP, 'StatusCode');
            $subStatusCode->setAttribute('Value', $this->status['SubCode']);
            $statusCode->appendChild($subStatusCode);
        }

        if (!is_null($this->status['Message'])) {
            Utils::addString($status, Constants::NS_SAMLP, 'StatusMessage', $this->status['Message']);
        }

        return $root;
    }
}
