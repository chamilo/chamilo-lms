<?php

namespace SAML2\XML\md;

use SAML2\Constants;
use SAML2\Utils;
use Webmozart\Assert\Assert;

/**
 * Class representing SAML 2 Metadata AttributeConsumingService element.
 *
 * @package SimpleSAMLphp
 */
class AttributeConsumingService
{
    /**
     * The index of this AttributeConsumingService.
     *
     * @var int
     */
    public $index;

    /**
     * Whether this is the default AttributeConsumingService.
     *
     * @var bool|null
     */
    public $isDefault = null;

    /**
     * The ServiceName of this AttributeConsumingService.
     *
     * This is an associative array with language => translation.
     *
     * @var array
     */
    public $ServiceName = [];

    /**
     * The ServiceDescription of this AttributeConsumingService.
     *
     * This is an associative array with language => translation.
     *
     * @var array
     */
    public $ServiceDescription = [];

    /**
     * The RequestedAttribute elements.
     *
     * This is an array of SAML_RequestedAttributeType elements.
     *
     * @var \SAML2\XML\md\RequestedAttribute[]
     */
    public $RequestedAttribute = [];


    /**
     * Initialize / parse an AttributeConsumingService.
     *
     * @param \DOMElement|null $xml The XML element we should load.
     * @throws \Exception
     */
    public function __construct(\DOMElement $xml = null)
    {
        if ($xml === null) {
            return;
        }

        if (!$xml->hasAttribute('index')) {
            throw new \Exception('Missing index on AttributeConsumingService.');
        }
        $this->setIndex(intval($xml->getAttribute('index')));

        $this->setIsDefault(Utils::parseBoolean($xml, 'isDefault', null));

        $this->setServiceName(Utils::extractLocalizedStrings($xml, Constants::NS_MD, 'ServiceName'));
        if ($this->getServiceName() === []) {
            throw new \Exception('Missing ServiceName in AttributeConsumingService.');
        }

        $this->setServiceDescription(Utils::extractLocalizedStrings($xml, Constants::NS_MD, 'ServiceDescription'));

        foreach (Utils::xpQuery($xml, './saml_metadata:RequestedAttribute') as $ra) {
            $this->addRequestedAttribute(new RequestedAttribute($ra));
        }
    }


    /**
     * Collect the value of the index-property
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }


    /**
     * Set the value of the index-property
     * @param int $index
     * @return void
     */
    public function setIndex($index)
    {
        Assert::integer($index);
        $this->index = $index;
    }


    /**
     * Collect the value of the isDefault-property
     * @return boolean|null
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }


    /**
     * Set the value of the isDefault-property
     * @param boolean|null $flag
     * @return void
     */
    public function setIsDefault($flag = null)
    {
        Assert::nullOrBoolean($flag);
        $this->isDefault = $flag;
    }


    /**
     * Collect the value of the ServiceName-property
     * @return string[]
     */
    public function getServiceName()
    {
        return $this->ServiceName;
    }


    /**
     * Set the value of the ServiceName-property
     * @param string[] $serviceName
     * @return void
     */
    public function setServiceName(array $serviceName)
    {
        $this->ServiceName = $serviceName;
    }


    /**
     * Collect the value of the ServiceDescription-property
     * @return string[]
     */
    public function getServiceDescription()
    {
        return $this->ServiceDescription;
    }


    /**
     * Set the value of the ServiceDescription-property
     * @param string[] $serviceDescription
     * @return void
     */
    public function setServiceDescription(array $serviceDescription)
    {
        $this->ServiceDescription = $serviceDescription;
    }


    /**
     * Collect the value of the RequestedAttribute-property
     * @return \SAML2\XML\md\RequestedAttribute[]
     */
    public function getRequestedAttribute()
    {
        return $this->RequestedAttribute;
    }


    /**
     * Set the value of the RequestedAttribute-property
     * @param \SAML2\XML\md\RequestedAttribute[] $requestedAttribute
     * @return void
     */
    public function setRequestedAttribute(array $requestedAttribute)
    {
        $this->RequestedAttribute = $requestedAttribute;
    }


    /**
     * Add the value to the RequestedAttribute-property
     * @param \SAML2\XML\md\RequestedAttribute $requestedAttribute
     * @return void
     */
    public function addRequestedAttribute(RequestedAttribute $requestedAttribute)
    {
        $this->RequestedAttribute[] = $requestedAttribute;
    }


    /**
     * Convert to \DOMElement.
     *
     * @param \DOMElement $parent The element we should append this AttributeConsumingService to.
     * @return \DOMElement
     */
    public function toXML(\DOMElement $parent)
    {
        Assert::integer($this->getIndex());
        Assert::nullOrBoolean($this->getIsDefault());
        Assert::isArray($this->getServiceName());
        Assert::isArray($this->getServiceDescription());
        Assert::isArray($this->getRequestedAttribute());

        $doc = $parent->ownerDocument;

        $e = $doc->createElementNS(Constants::NS_MD, 'md:AttributeConsumingService');
        $parent->appendChild($e);

        $e->setAttribute('index', strval($this->getIndex()));

        if ($this->getIsDefault() === true) {
            $e->setAttribute('isDefault', 'true');
        } elseif ($this->getIsDefault() === false) {
            $e->setAttribute('isDefault', 'false');
        }

        Utils::addStrings($e, Constants::NS_MD, 'md:ServiceName', true, $this->getServiceName());
        Utils::addStrings($e, Constants::NS_MD, 'md:ServiceDescription', true, $this->getServiceDescription());

        foreach ($this->getRequestedAttribute() as $ra) {
            $ra->toXML($e);
        }

        return $e;
    }
}
