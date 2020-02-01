<?php

namespace SAML2\XML\md;

use SAML2\Constants;
use Webmozart\Assert\Assert;

/**
 * Class representing SAML 2 EndpointType.
 *
 * @package SimpleSAMLphp
 */
class EndpointType
{
    /**
     * The binding for this endpoint.
     *
     * @var string
     */
    public $Binding;

    /**
     * The URI to this endpoint.
     *
     * @var string
     */
    public $Location;

    /**
     * The URI where responses can be delivered.
     *
     * @var string|null
     */
    public $ResponseLocation = null;

    /**
     * Extra (namespace qualified) attributes.
     *
     * @var array
     */
    private $attributes = [];


    /**
     * Initialize an EndpointType.
     *
     * @param \DOMElement|null $xml The XML element we should load.
     * @throws \Exception
     */
    public function __construct(\DOMElement $xml = null)
    {
        if ($xml === null) {
            return;
        }

        if (!$xml->hasAttribute('Binding')) {
            throw new \Exception('Missing Binding on '.$xml->tagName);
        }
        $this->setBinding($xml->getAttribute('Binding'));

        if (!$xml->hasAttribute('Location')) {
            throw new \Exception('Missing Location on '.$xml->tagName);
        }
        $this->setLocation($xml->getAttribute('Location'));

        if ($xml->hasAttribute('ResponseLocation')) {
            $this->setResponseLocation($xml->getAttribute('ResponseLocation'));
        }

        foreach ($xml->attributes as $a) {
            if ($a->namespaceURI === null) {
                continue; /* Not namespace-qualified -- skip. */
            }
            $fullName = '{'.$a->namespaceURI.'}'.$a->localName;
            $this->attributes[$fullName] = [
                'qualifiedName' => $a->nodeName,
                'namespaceURI' => $a->namespaceURI,
                'value' => $a->value,
            ];
        }
    }


    /**
     * Check if a namespace-qualified attribute exists.
     *
     * @param  string  $namespaceURI The namespace URI.
     * @param  string  $localName    The local name.
     * @return boolean true if the attribute exists, false if not.
     */
    public function hasAttributeNS($namespaceURI, $localName)
    {
        Assert::string($namespaceURI);
        Assert::string($localName);

        $fullName = '{'.$namespaceURI.'}'.$localName;

        return isset($this->attributes[$fullName]);
    }


    /**
     * Get a namespace-qualified attribute.
     *
     * @param  string $namespaceURI The namespace URI.
     * @param  string $localName    The local name.
     * @return string The value of the attribute, or an empty string if the attribute does not exist.
     */
    public function getAttributeNS($namespaceURI, $localName)
    {
        Assert::string($namespaceURI);
        Assert::string($localName);

        $fullName = '{'.$namespaceURI.'}'.$localName;
        if (!isset($this->attributes[$fullName])) {
            return '';
        }

        return $this->attributes[$fullName]['value'];
    }


    /**
     * Get a namespace-qualified attribute.
     *
     * @param string $namespaceURI  The namespace URI.
     * @param string $qualifiedName The local name.
     * @param string $value         The attribute value.
     * @throws \Exception
     * @return void
     */
    public function setAttributeNS($namespaceURI, $qualifiedName, $value)
    {
        Assert::string($namespaceURI);
        Assert::string($qualifiedName);

        $name = explode(':', $qualifiedName, 2);
        if (count($name) < 2) {
            throw new \Exception('Not a qualified name.');
        }
        $localName = $name[1];

        $fullName = '{'.$namespaceURI.'}'.$localName;
        $this->attributes[$fullName] = [
            'qualifiedName' => $qualifiedName,
            'namespaceURI' => $namespaceURI,
            'value' => $value,
        ];
    }


    /**
     * Remove a namespace-qualified attribute.
     *
     * @param string $namespaceURI The namespace URI.
     * @param string $localName    The local name.
     * @return void
     */
    public function removeAttributeNS($namespaceURI, $localName)
    {
        Assert::string($namespaceURI);
        assert::string($localName);

        $fullName = '{'.$namespaceURI.'}'.$localName;
        unset($this->attributes[$fullName]);
    }


    /**
     * Collect the value of the Binding-property
     * @return string
     */
    public function getBinding()
    {
        return $this->Binding;
    }


    /**
     * Set the value of the Binding-property
     * @param string $binding
     * @return void
     */
    public function setBinding($binding)
    {
        Assert::string($binding);
        $this->Binding = $binding;
    }


    /**
     * Collect the value of the Location-property
     * @return string|null
     */
    public function getLocation()
    {
        return $this->Location;
    }


    /**
     * Set the value of the Location-property
     * @param string|null $location
     * @return void
     */
    public function setLocation($location)
    {
        Assert::nullOrString($location);
        $this->Location = $location;
    }


    /**
     * Collect the value of the ResponseLocation-property
     * @return string|null
     */
    public function getResponseLocation()
    {
        return $this->ResponseLocation;
    }


    /**
     * Set the value of the ResponseLocation-property
     * @param string|null $responseLocation
     * @return void
     */
    public function setResponseLocation($responseLocation)
    {
        Assert::nullOrString($responseLocation);
        $this->ResponseLocation = $responseLocation;
    }


    /**
     * Add this endpoint to an XML element.
     *
     * @param \DOMElement $parent The element we should append this endpoint to.
     * @param string     $name   The name of the element we should create.
     * @return \DOMElement
     */
    public function toXML(\DOMElement $parent, $name)
    {
        Assert::string($name);
        Assert::string($this->getBinding());
        Assert::string($this->getLocation());
        Assert::nullOrString($this->getResponseLocation());

        $e = $parent->ownerDocument->createElementNS(Constants::NS_MD, $name);
        $parent->appendChild($e);

        $e->setAttribute('Binding', $this->getBinding());
        $e->setAttribute('Location', $this->getLocation());

        if ($this->getResponseLocation() !== null) {
            $e->setAttribute('ResponseLocation', $this->getResponseLocation());
        }

        foreach ($this->attributes as $a) {
            $e->setAttributeNS($a['namespaceURI'], $a['qualifiedName'], $a['value']);
        }

        return $e;
    }
}
