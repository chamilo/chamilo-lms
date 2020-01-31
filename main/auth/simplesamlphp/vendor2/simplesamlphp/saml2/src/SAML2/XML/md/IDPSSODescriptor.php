<?php

namespace SAML2\XML\md;

use SAML2\Constants;
use SAML2\Utils;
use SAML2\XML\saml\Attribute;
use Webmozart\Assert\Assert;

/**
 * Class representing SAML 2 IDPSSODescriptor.
 *
 * @package SimpleSAMLphp
 */
class IDPSSODescriptor extends SSODescriptorType
{
    /**
     * Whether AuthnRequests sent to this IdP should be signed.
     *
     * @var bool|null
     */
    public $WantAuthnRequestsSigned = null;

    /**
     * List of SingleSignOnService endpoints.
     *
     * Array with EndpointType objects.
     *
     * @var \SAML2\XML\md\EndpointType[]
     */
    public $SingleSignOnService = [];

    /**
     * List of NameIDMappingService endpoints.
     *
     * Array with EndpointType objects.
     *
     * @var \SAML2\XML\md\EndpointType[]
     */
    public $NameIDMappingService = [];

    /**
     * List of AssertionIDRequestService endpoints.
     *
     * Array with EndpointType objects.
     *
     * @var \SAML2\XML\md\EndpointType[]
     */
    public $AssertionIDRequestService = [];

    /**
     * List of supported attribute profiles.
     *
     * Array with strings.
     *
     * @var array
     */
    public $AttributeProfile = [];

    /**
     * List of supported attributes.
     *
     * Array with \SAML2\XML\saml\Attribute objects.
     *
     * @var \SAML2\XML\saml\Attribute[]
     */
    public $Attribute = [];


    /**
     * Initialize an IDPSSODescriptor.
     *
     * @param \DOMElement|null $xml The XML element we should load.
     */
    public function __construct(\DOMElement $xml = null)
    {
        parent::__construct('md:IDPSSODescriptor', $xml);

        if ($xml === null) {
            return;
        }

        $this->setWantAuthnRequestsSigned(Utils::parseBoolean($xml, 'WantAuthnRequestsSigned', null));

        foreach (Utils::xpQuery($xml, './saml_metadata:SingleSignOnService') as $ep) {
            $this->addSingleSignOnService(new EndpointType($ep));
        }

        foreach (Utils::xpQuery($xml, './saml_metadata:NameIDMappingService') as $ep) {
            $this->addNameIDMappingService(new EndpointType($ep));
        }

        foreach (Utils::xpQuery($xml, './saml_metadata:AssertionIDRequestService') as $ep) {
            $this->addAssertionIDRequestService(new EndpointType($ep));
        }

        $this->setAttributeProfile(Utils::extractStrings($xml, Constants::NS_MD, 'AttributeProfile'));

        foreach (Utils::xpQuery($xml, './saml_assertion:Attribute') as $a) {
            $this->addAttribute(new Attribute($a));
        }
    }


    /**
     * Collect the value of the WantAuthnRequestsSigned-property
     * @return bool|null
     */
    public function wantAuthnRequestsSigned()
    {
        return $this->WantAuthnRequestsSigned;
    }


    /**
     * Set the value of the WantAuthnRequestsSigned-property
     * @param bool|null $flag
     * @return void
     */
    public function setWantAuthnRequestsSigned($flag = null)
    {
        Assert::nullOrBoolean($flag);
        $this->WantAuthnRequestsSigned = $flag;
    }


    /**
     * Collect the value of the SingleSignOnService-property
     * @return \SAML2\XML\md\EndpointType[]
     */
    public function getSingleSignOnService()
    {
        return $this->SingleSignOnService;
    }


    /**
     * Set the value of the SingleSignOnService-property
     * @param array $singleSignOnService
     * @return void
     */
    public function setSingleSignOnService(array $singleSignOnService)
    {
        $this->SingleSignOnService = $singleSignOnService;
    }


    /**
     * Add the value to the SingleSignOnService-property
     * @param \SAML2\XML\md\EndpointType $singleSignOnService
     * @return void
     */
    public function addSingleSignOnService(EndpointType $singleSignOnService)
    {
        $this->SingleSignOnService[] = $singleSignOnService;
    }


    /**
     * Collect the value of the NameIDMappingService-property
     * @return \SAML2\XML\md\EndpointType[]
     */
    public function getNameIDMappingService()
    {
        return $this->NameIDMappingService;
    }


    /**
     * Set the value of the NameIDMappingService-property
     * @param array $nameIDMappingService
     * @return void
     */
    public function setNameIDMappingService(array $nameIDMappingService)
    {
        $this->NameIDMappingService = $nameIDMappingService;
    }


    /**
     * Add the value to the NameIDMappingService-property
     * @param \SAML2\XML\md\EndpointType $nameIDMappingService
     * @return void
     */
    public function addNameIDMappingService(EndpointType $nameIDMappingService)
    {
        $this->NameIDMappingService[] = $nameIDMappingService;
    }


    /**
     * Collect the value of the AssertionIDRequestService-property
     * @return \SAML2\XML\md\EndpointType[]
     */
    public function getAssertionIDRequestService()
    {
        return $this->AssertionIDRequestService;
    }


    /**
     * Set the value of the AssertionIDRequestService-property
     * @param array $assertionIDRequestService
     * @return void
     */
    public function setAssertionIDRequestService(array $assertionIDRequestService)
    {
        $this->AssertionIDRequestService = $assertionIDRequestService;
    }


    /**
     * Add the value to the AssertionIDRequestService-property
     * @param \SAML2\XML\md\EndpointType $assertionIDRequestService
     * @return void
     */
    public function addAssertionIDRequestService(EndpointType $assertionIDRequestService)
    {
        $this->AssertionIDRequestService[] = $assertionIDRequestService;
    }


    /**
     * Collect the value of the AttributeProfile-property
     * @return array
     */
    public function getAttributeProfile()
    {
        return $this->AttributeProfile;
    }


    /**
     * Set the value of the AttributeProfile-property
     * @param array $attributeProfile
     * @return void
     */
    public function setAttributeProfile(array $attributeProfile)
    {
        $this->AttributeProfile = $attributeProfile;
    }


    /**
     * Collect the value of the Attribute-property
     * @return \SAML2\XML\saml\Attribute[]
     */
    public function getAttribute()
    {
        return $this->Attribute;
    }


    /**
     * Set the value of the Attribute-property
     * @param array $attribute
     * @return void
     */
    public function setAttribute(array $attribute)
    {
        $this->Attribute = $attribute;
    }


    /**
     * Addthe value to the Attribute-property
     * @param \SAML2\XML\saml\Attribute $attribute
     * @return void
     */
    public function addAttribute(Attribute $attribute)
    {
        $this->Attribute[] = $attribute;
    }


    /**
     * Add this IDPSSODescriptor to an EntityDescriptor.
     *
     * @param \DOMElement $parent The EntityDescriptor we should append this IDPSSODescriptor to.
     * @return \DOMElement
     */
    public function toXML(\DOMElement $parent)
    {
        Assert::nullOrBoolean($this->WantAuthnRequestsSigned());
        Assert::isArray($this->getSingleSignOnService());
        Assert::isArray($this->getNameIDMappingService());
        Assert::isArray($this->getAssertionIDRequestService());
        Assert::isArray($this->getAttributeProfile());
        Assert::isArray($this->getAttribute());

        $e = parent::toXML($parent);

        if ($this->WantAuthnRequestsSigned() === true) {
            $e->setAttribute('WantAuthnRequestsSigned', 'true');
        } elseif ($this->WantAuthnRequestsSigned() === false) {
            $e->setAttribute('WantAuthnRequestsSigned', 'false');
        }

        foreach ($this->getSingleSignOnService() as $ep) {
            $ep->toXML($e, 'md:SingleSignOnService');
        }

        foreach ($this->getNameIDMappingService() as $ep) {
            $ep->toXML($e, 'md:NameIDMappingService');
        }

        foreach ($this->getAssertionIDRequestService() as $ep) {
            $ep->toXML($e, 'md:AssertionIDRequestService');
        }

        Utils::addStrings($e, Constants::NS_MD, 'md:AttributeProfile', false, $this->getAttributeProfile());

        foreach ($this->getAttribute() as $a) {
            $a->toXML($e);
        }

        return $e;
    }
}
