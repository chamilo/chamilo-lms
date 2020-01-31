<?php

namespace SAML2\XML\mdrpi;

use SAML2\Utils;
use Webmozart\Assert\Assert;

/**
 * Class for handling the mdrpi:RegistrationInfo element.
 *
 * @link: http://docs.oasis-open.org/security/saml/Post2.0/saml-metadata-rpi/v1.0/saml-metadata-rpi-v1.0.pdf
 * @package SimpleSAMLphp
 */
class RegistrationInfo
{
    /**
     * The identifier of the metadata registration authority.
     *
     * @var string
     */
    public $registrationAuthority;

    /**
     * The registration timestamp for the metadata, as a UNIX timestamp.
     *
     * @var int|null
     */
    public $registrationInstant;

    /**
     * Link to registration policy for this metadata.
     *
     * This is an associative array with language=>URL.
     *
     * @var array
     */
    public $RegistrationPolicy = [];


    /**
     * Create/parse a mdrpi:RegistrationInfo element.
     *
     * @param \DOMElement|null $xml The XML element we should load.
     * @throws \Exception
     */
    public function __construct(\DOMElement $xml = null)
    {
        if ($xml === null) {
            return;
        }

        if (!$xml->hasAttribute('registrationAuthority')) {
            throw new \Exception('Missing required attribute "registrationAuthority" in mdrpi:RegistrationInfo element.');
        }
        $this->setRegistrationAuthority($xml->getAttribute('registrationAuthority'));

        if ($xml->hasAttribute('registrationInstant')) {
            $this->setRegistrationInstant(Utils::xsDateTimeToTimestamp($xml->getAttribute('registrationInstant')));
        }

        $this->setRegistrationPolicy(Utils::extractLocalizedStrings($xml, Common::NS_MDRPI, 'RegistrationPolicy'));
    }


    /**
     * Collect the value of the RegistrationAuthority-property
     * @return string
     */
    public function getRegistrationAuthority()
    {
        return $this->registrationAuthority;
    }


    /**
     * Set the value of the registrationAuthority-property
     * @param string $registrationAuthority
     * @return void
     */
    public function setRegistrationAuthority($registrationAuthority)
    {
        Assert::string($registrationAuthority);
        $this->registrationAuthority = $registrationAuthority;
    }


    /**
     * Collect the value of the registrationInstant-property
     * @return int|null
     */
    public function getRegistrationInstant()
    {
        return $this->registrationInstant;
    }


    /**
     * Set the value of the registrationInstant-property
     * @param int|null $registrationInstant
     * @return void
     */
    public function setRegistrationInstant($registrationInstant = null)
    {
        Assert::nullOrInteger($registrationInstant);
        $this->registrationInstant = $registrationInstant;
    }


    /**
     * Collect the value of the RegistrationPolicy-property
     * @return array
     */
    public function getRegistrationPolicy()
    {
        return $this->RegistrationPolicy;
    }


    /**
     * Set the value of the RegistrationPolicy-property
     * @param array $registrationPolicy
     * @return void
     */
    public function setRegistrationPolicy(array $registrationPolicy)
    {
        $this->RegistrationPolicy = $registrationPolicy;
    }


    /**
     * Convert this element to XML.
     *
     * @param \DOMElement $parent The element we should append to.
     * @return \DOMElement
     */
    public function toXML(\DOMElement $parent)
    {
        Assert::string($this->getRegistrationAuthority());
        Assert::nullOrInteger($this->getRegistrationInstant());
        Assert::isArray($this->getRegistrationPolicy());

        $registrationAuthority = $this->getRegistrationAuthority();
        if (empty($registrationAuthority)) {
            throw new \Exception('Missing required registration authority.');
        }

        $doc = $parent->ownerDocument;

        $e = $doc->createElementNS(Common::NS_MDRPI, 'mdrpi:RegistrationInfo');
        $parent->appendChild($e);

        $e->setAttribute('registrationAuthority', $this->getRegistrationAuthority());

        if ($this->getRegistrationInstant() !== null) {
            $e->setAttribute('registrationInstant', gmdate('Y-m-d\TH:i:s\Z', $this->getRegistrationInstant()));
        }

        Utils::addStrings($e, Common::NS_MDRPI, 'mdrpi:RegistrationPolicy', true, $this->getRegistrationPolicy());

        return $e;
    }
}
