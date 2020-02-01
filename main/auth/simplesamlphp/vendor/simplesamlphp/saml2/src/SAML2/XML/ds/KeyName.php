<?php

namespace SAML2\XML\ds;

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use SAML2\Utils;
use Webmozart\Assert\Assert;

/**
 * Class representing a ds:KeyName element.
 *
 * @package SimpleSAMLphp
 */
class KeyName
{
    /**
     * The key name.
     *
     * @var string
     */
    public $name;


    /**
     * Initialize a KeyName element.
     *
     * @param \DOMElement|null $xml The XML element we should load.
     */
    public function __construct(\DOMElement $xml = null)
    {
        if ($xml === null) {
            return;
        }

        $this->setName($xml->textContent);
    }


    /**
     * Collect the value of the name-property
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * Set the value of the name-property
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        Assert::nullOrString($name);
        $this->name = $name;
    }


    /**
     * Convert this KeyName element to XML.
     *
     * @param \DOMElement $parent The element we should append this KeyName element to.
     * @return \DOMElement
     */
    public function toXML(\DOMElement $parent)
    {
        Assert::string($this->name);

        return Utils::addString($parent, XMLSecurityDSig::XMLDSIGNS, 'ds:KeyName', $this->name);
    }
}
