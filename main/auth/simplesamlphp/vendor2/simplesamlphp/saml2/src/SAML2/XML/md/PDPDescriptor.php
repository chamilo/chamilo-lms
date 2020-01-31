<?php

namespace SAML2\XML\md;

use SAML2\Constants;
use SAML2\Utils;
use Webmozart\Assert\Assert;

/**
 * Class representing SAML 2 metadata PDPDescriptor.
 *
 * @package SimpleSAMLphp
 */
class PDPDescriptor extends RoleDescriptor
{
    /**
     * List of AuthzService endpoints.
     *
     * Array with EndpointType objects.
     *
     * @var \SAML2\XML\md\EndpointType[]
     */
    public $AuthzService = [];

    /**
     * List of AssertionIDRequestService endpoints.
     *
     * Array with EndpointType objects.
     *
     * @var \SAML2\XML\md\EndpointType[]
     */
    public $AssertionIDRequestService = [];

    /**
     * List of supported NameID formats.
     *
     * Array of strings.
     *
     * @var string[]
     */
    public $NameIDFormat = [];


    /**
     * Initialize an IDPSSODescriptor.
     *
     * @param \DOMElement|null $xml The XML element we should load.
     * @throws \Exception
     */
    public function __construct(\DOMElement $xml = null)
    {
        parent::__construct('md:PDPDescriptor', $xml);

        if ($xml === null) {
            return;
        }

        foreach (Utils::xpQuery($xml, './saml_metadata:AuthzService') as $ep) {
            $this->addAuthzService(new EndpointType($ep));
        }
        if ($this->getAuthzService() !== []) {
            throw new \Exception('Must have at least one AuthzService in PDPDescriptor.');
        }

        foreach (Utils::xpQuery($xml, './saml_metadata:AssertionIDRequestService') as $ep) {
            $this->addAssertionIDRequestService(new EndpointType($ep));
        }

        $this->setNameIDFormat(Utils::extractStrings($xml, Constants::NS_MD, 'NameIDFormat'));
    }


    /**
     * Collect the value of the AuthzService-property
     * @return \SAML2\XML\md\EndpointType[]
     */
    public function getAuthzService()
    {
        return $this->AuthzService;
    }


    /**
     * Set the value of the AuthzService-property
     * @param \SAML2\XML\md\EndpointType[] $authzService
     * @return void
     */
    public function setAuthzService(array $authzService = [])
    {
        $this->AuthzService = $authzService;
    }


    /**
     * Add the value to the AuthzService-property
     * @param \SAML2\XML\md\EndpointType $authzService
     * @return void
     */
    public function addAuthzService(EndpointType $authzService)
    {
        Assert::isInstanceOf($authzService, EndpointType::class);
        $this->AuthzService[] = $authzService;
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
     * @param \SAML2\XML\md\EndpointType[] $assertionIDRequestService
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
        Assert::isInstanceOf($assertionIDRequestService, EndpointType::class);
        $this->AssertionIDRequestService[] = $assertionIDRequestService;
    }


    /**
     * Collect the value of the NameIDFormat-property
     * @return string[]
     */
    public function getNameIDFormat()
    {
        return $this->NameIDFormat;
    }


    /**
     * Set the value of the NameIDFormat-property
     * @param string[] $nameIDFormat
     * @return void
     */
    public function setNameIDFormat(array $nameIDFormat)
    {
        $this->NameIDFormat = $nameIDFormat;
    }


    /**
     * Add this PDPDescriptor to an EntityDescriptor.
     *
     * @param \DOMElement $parent The EntityDescriptor we should append this IDPSSODescriptor to.
     * @return \DOMElement
     */
    public function toXML(\DOMElement $parent)
    {
        Assert::isArray($authzService = $this->getAuthzService());
        Assert::notEmpty($authzService);
        Assert::isArray($this->getAssertionIDRequestService());
        Assert::isArray($this->getNameIDFormat());

        $e = parent::toXML($parent);

        foreach ($this->getAuthzService() as $ep) {
            $ep->toXML($e, 'md:AuthzService');
        }

        foreach ($this->getAssertionIDRequestService() as $ep) {
            $ep->toXML($e, 'md:AssertionIDRequestService');
        }

        Utils::addStrings($e, Constants::NS_MD, 'md:NameIDFormat', false, $this->getNameIDFormat());

        return $e;
    }
}
