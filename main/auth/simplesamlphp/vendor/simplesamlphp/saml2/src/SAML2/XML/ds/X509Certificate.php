<?php

namespace SAML2\XML\ds;

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use SAML2\Utils;
use Webmozart\Assert\Assert;

/**
 * Class representing a ds:X509Certificate element.
 *
 * @package SimpleSAMLphp
 */
class X509Certificate
{
    /**
     * The base64-encoded certificate.
     *
     * @var string
     */
    public $certificate;


    /**
     * Initialize an X509Certificate element.
     *
     * @param \DOMElement|null $xml The XML element we should load.
     */
    public function __construct(\DOMElement $xml = null)
    {
        if ($xml === null) {
            return;
        }

        $this->setCertificate($xml->textContent);
    }


    /**
     * Collect the value of the certificate-property
     * @return string
     */
    public function getCertificate()
    {
        return $this->certificate;
    }


    /**
     * Set the value of the certificate-property
     * @param string $certificate
     * @return void
     */
    public function setCertificate($certificate)
    {
        Assert::string($certificate);
        $this->certificate = $certificate;
    }


    /**
     * Convert this X509Certificate element to XML.
     *
     * @param \DOMElement $parent The element we should append this X509Certificate element to.
     * @return \DOMElement
     */
    public function toXML(\DOMElement $parent)
    {
        Assert::string($this->certificate);
        return Utils::addString($parent, XMLSecurityDSig::XMLDSIGNS, 'ds:X509Certificate', $this->getCertificate());
    }
}
