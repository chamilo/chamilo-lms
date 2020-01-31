<?php
/**
 * Base class corresponding to the BaseID element.
 *
 * @author Jaime Pérez Crespo, UNINETT AS <jaime.perez@uninett.no>
 * @package simplesamlphp/saml2
 */

namespace SAML2\XML\saml;

use SAML2\Constants;
use SAML2\DOMDocumentFactory;
use Webmozart\Assert\Assert;

abstract class BaseIDType
{
    /**
     * The security or administrative domain that qualifies the identifier.
     * This attribute provides a means to federate identifiers from disparate user stores without collision.
     *
     * @see saml-core-2.0-os
     *
     * @var string|null
     */
    public $NameQualifier = null;

    /**
     * Further qualifies an identifier with the name of a service provider or affiliation of providers.
     * This attribute provides an additional means to federate identifiers on the basis of the relying party or parties.
     *
     * @see saml-core-2.0-os
     *
     * @var string|null
     */
    public $SPNameQualifier = null;

    /**
     * The name for this BaseID.
     *
     * Override in classes extending this class to get the desired name.
     *
     * @var string
     */
    protected $nodeName;

    /**
     * @var \DOMElement
     */
    protected $element;


    /**
     * Initialize a saml:BaseID, either from scratch or from an existing \DOMElement.
     *
     * @param \DOMElement|null $xml The XML element we should load, if any.
     */
    public function __construct(\DOMElement $xml = null)
    {
        if ($xml === null) {
            return;
        }

        $this->element = $xml;

        if ($xml->hasAttribute('NameQualifier')) {
            $this->setNameQualifier($xml->getAttribute('NameQualifier'));
        }

        if ($xml->hasAttribute('SPNameQualifier')) {
            $this->setSPNameQualifier($xml->getAttribute('SPNameQualifier'));
        }
    }


    /**
     * Collect the value of the NameQualifier-property
     * @return string|null
     */
    public function getNameQualifier()
    {
        return $this->NameQualifier;
    }


    /**
     * Set the value of the NameQualifier-property
     * @param string|null $nameQualifier
     * @return void
     */
    public function setNameQualifier($nameQualifier = null)
    {
        Assert::nullOrString($nameQualifier);
        $this->NameQualifier = $nameQualifier;
    }


    /**
     * Collect the value of the SPNameQualifier-property
     * @return string|null
     */
    public function getSPNameQualifier()
    {
        return $this->SPNameQualifier;
    }


    /**
     * Set the value of the SPNameQualifier-property
     * @param string|null $spNameQualifier
     * @return void
     */
    public function setSPNameQualifier($spNameQualifier = null)
    {
        Assert::nullOrString($spNameQualifier);
        $this->SPNameQualifier = $spNameQualifier;
    }


    /**
     * Convert this BaseID to XML.
     *
     * @param \DOMElement $parent The element we are converting to XML.
     * @return \DOMElement The XML element after adding the data corresponding to this BaseID.
     */
    public function toXML(\DOMElement $parent = null)
    {
        Assert::nullOrString($this->getNameQualifier());
        Assert::nullOrString($this->getSPNameQualifier());

        if ($parent === null) {
            $parent = DOMDocumentFactory::create();
            $doc = $parent;
        } else {
            $doc = $parent->ownerDocument;
        }
        $element = $doc->createElementNS(Constants::NS_SAML, $this->nodeName);
        $parent->appendChild($element);

        if ($this->getNameQualifier() !== null) {
            $element->setAttribute('NameQualifier', $this->getNameQualifier());
        }

        if ($this->getSPNameQualifier() !== null) {
            $element->setAttribute('SPNameQualifier', $this->getSPNameQualifier());
        }

        return $element;
    }


    /**
     * Get a string representation of this BaseIDType object.
     *
     * @return string The resulting XML, as a string.
     */
    public function __toString()
    {
        $doc = DOMDocumentFactory::create();
        $root = $doc->createElementNS(Constants::NS_SAML, 'root');
        $ele = $this->toXML($root);

        return $doc->saveXML($ele);
    }
}
