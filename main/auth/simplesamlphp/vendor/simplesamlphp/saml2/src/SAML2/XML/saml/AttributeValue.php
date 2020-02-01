<?php

namespace SAML2\XML\saml;

use DOMElement;
use SAML2\Constants;
use SAML2\DOMDocumentFactory;
use SAML2\Utils;
use Webmozart\Assert\Assert;

/**
 * Serializable class representing an AttributeValue.
 *
 * @package SimpleSAMLphp
 */
class AttributeValue implements \Serializable
{
    /**
     * The raw \DOMElement representing this value.
     *
     * @var \DOMElement
     */
    public $element;


    /**
     * Create an AttributeValue.
     *
     * @param mixed $value The value of this element. Can be one of:
     *  - string                      Create an attribute value with a simple string.
     *  - \DOMElement(AttributeValue)  Create an attribute value of the given DOMElement.
     *  - \DOMElement                  Create an attribute value with the given DOMElement as a child.
     */
    public function __construct($value)
    {
        Assert::true(is_string($value) || $value instanceof DOMElement);

        if (is_string($value)) {
            $doc = DOMDocumentFactory::create();
            $this->setElement($doc->createElementNS(Constants::NS_SAML, 'saml:AttributeValue'));
            $this->getElement()->setAttributeNS(Constants::NS_XSI, 'xsi:type', 'xs:string');
            $this->getElement()->appendChild($doc->createTextNode($value));

            /* Make sure that the xs-namespace is available in the AttributeValue (for xs:string). */
            $this->getElement()->setAttributeNS(Constants::NS_XS, 'xs:tmp', 'tmp');
            $this->getElement()->removeAttributeNS(Constants::NS_XS, 'tmp');
            return;
        }

        if ($value->namespaceURI === Constants::NS_SAML && $value->localName === 'AttributeValue') {
            $this->setElement(Utils::copyElement($value));
            return;
        }

        $doc = DOMDocumentFactory::create();
        $this->setElement($doc->createElementNS(Constants::NS_SAML, 'saml:AttributeValue'));
        Utils::copyElement($value, $this->element);
    }


    /**
     * Collect the value of the element-property
     * @return \DOMElement
     */
    public function getElement()
    {
        return $this->element;
    }


    /**
     * Set the value of the element-property
     * @param \DOMElement $element
     * @return void
     */
    public function setElement(DOMElement $element)
    {
        $this->element = $element;
    }


    /**
     * Append this attribute value to an element.
     *
     * @param  \DOMElement $parent The element we should append this attribute value to.
     * @return \DOMElement The generated AttributeValue element.
     */
    public function toXML(DOMElement $parent)
    {
        Assert::isInstanceOf($this->getElement(), DOMElement::class);
        Assert::same($this->getElement()->namespaceURI, Constants::NS_SAML);
        Assert::same($this->element->localName, "AttributeValue");

        return Utils::copyElement($this->getElement(), $parent);
    }


    /**
     * Returns a plain text content of the attribute value.
     * @return string
     */
    public function getString()
    {
        return $this->element->textContent;
    }


    /**
     * Convert this attribute value to a string.
     *
     * If this element contains XML data, that data will be encoded as a string and returned.
     *
     * @return string This attribute value.
     */
    public function __toString()
    {
        Assert::isInstanceOf($this->getElement(), DOMElement::class);

        $doc = $this->getElement()->ownerDocument;

        $ret = '';
        foreach ($this->getElement()->childNodes as $c) {
            $ret .= $doc->saveXML($c);
        }

        return $ret;
    }


    /**
     * Serialize this AttributeValue.
     *
     * @return string The AttributeValue serialized.
     */
    public function serialize()
    {
        return serialize($this->getElement()->ownerDocument->saveXML($this->getElement()));
    }


    /**
     * Un-serialize this AttributeValue.
     *
     * @param string $serialized The serialized AttributeValue.
     * @return void
     */
    public function unserialize($serialized)
    {
        $doc = DOMDocumentFactory::fromString(unserialize($serialized));
        $this->setElement($doc->documentElement);
    }
}
