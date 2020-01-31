<?php

namespace SAML2\XML\md;

use SAML2\Constants;
use SAML2\Utils;
use SAML2\XML\saml\Attribute;
use Webmozart\Assert\Assert;

/**
 * Class representing SAML 2 metadata RequestedAttribute.
 *
 * @package SimpleSAMLphp
 */
class RequestedAttribute extends Attribute
{
    /**
     * Whether this attribute is required.
     *
     * @var bool|null
     */
    public $isRequired = null;


    /**
     * Initialize an RequestedAttribute.
     *
     * @param \DOMElement|null $xml The XML element we should load.
     */
    public function __construct(\DOMElement $xml = null)
    {
        parent::__construct($xml);

        if ($xml === null) {
            return;
        }

        $this->setIsRequired(Utils::parseBoolean($xml, 'isRequired', null));
    }


    /**
     * Collect the value of the isRequired-property
     * @return boolean|null
     */
    public function getIsRequired()
    {
        return $this->isRequired;
    }


    /**
     * Set the value of the isRequired-property
     * @param boolean|null $flag
     * @return void
     */
    public function setIsRequired($flag = null)
    {
        Assert::nullOrBoolean($flag);
        $this->isRequired = $flag;
    }


    /**
     * Convert this RequestedAttribute to XML.
     *
     * @param \DOMElement $parent The element we should append this RequestedAttribute to.
     * @return \DOMElement
     */
    public function toXML(\DOMElement $parent)
    {
        Assert::nullOrBoolean($this->isRequired);

        $e = $this->toXMLInternal($parent, Constants::NS_MD, 'md:RequestedAttribute');

        if ($this->getIsRequired() === true) {
            $e->setAttribute('isRequired', 'true');
        } elseif ($this->getIsRequired() === false) {
            $e->setAttribute('isRequired', 'false');
        }

        return $e;
    }
}
