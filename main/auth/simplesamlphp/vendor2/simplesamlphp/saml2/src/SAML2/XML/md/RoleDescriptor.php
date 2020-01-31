<?php

namespace SAML2\XML\md;

use SAML2\Constants;
use SAML2\SignedElementHelper;
use SAML2\Utils;
use SAML2\XML\Chunk;
use Webmozart\Assert\Assert;

/**
 * Class representing SAML 2 RoleDescriptor element.
 *
 * @package SimpleSAMLphp
 */
class RoleDescriptor extends SignedElementHelper
{
    /**
     * The name of this descriptor element.
     *
     * @var string
     */
    private $elementName;

    /**
     * The ID of this element.
     *
     * @var string|null
     */
    public $ID;

    /**
     * How long this element is valid, as a unix timestamp.
     *
     * @var int|null
     */
    public $validUntil;

    /**
     * The length of time this element can be cached, as string.
     *
     * @var string|null
     */
    public $cacheDuration;

    /**
     * List of supported protocols.
     *
     * @var array
     */
    public $protocolSupportEnumeration = [];

    /**
     * Error URL for this role.
     *
     * @var string|null
     */
    public $errorURL;

    /**
     * Extensions on this element.
     *
     * Array of extension elements.
     *
     * @var array
     */
    public $Extensions = [];

    /**
     * KeyDescriptor elements.
     *
     * Array of \SAML2\XML\md\KeyDescriptor elements.
     *
     * @var \SAML2\XML\md\KeyDescriptor[]
     */
    public $KeyDescriptor = [];

    /**
     * Organization of this role.
     *
     * @var \SAML2\XML\md\Organization|null
     */
    public $Organization = null;

    /**
     * ContactPerson elements for this role.
     *
     * Array of \SAML2\XML\md\ContactPerson objects.
     *
     * @var \SAML2\XML\md\ContactPerson[]
     */
    public $ContactPerson = [];


    /**
     * Initialize a RoleDescriptor.
     *
     * @param string          $elementName The name of this element.
     * @param \DOMElement|null $xml         The XML element we should load.
     * @throws \Exception
     */
    protected function __construct($elementName, \DOMElement $xml = null)
    {
        Assert::string($elementName);

        parent::__construct($xml);
        $this->elementName = $elementName;

        if ($xml === null) {
            return;
        }

        if ($xml->hasAttribute('ID')) {
            $this->setID($xml->getAttribute('ID'));
        }
        if ($xml->hasAttribute('validUntil')) {
            $this->setValidUntil(Utils::xsDateTimeToTimestamp($xml->getAttribute('validUntil')));
        }
        if ($xml->hasAttribute('cacheDuration')) {
            $this->setCacheDuration($xml->getAttribute('cacheDuration'));
        }

        if (!$xml->hasAttribute('protocolSupportEnumeration')) {
            throw new \Exception('Missing protocolSupportEnumeration attribute on '.$xml->localName);
        }
        $this->setProtocolSupportEnumeration(preg_split('/[\s]+/', $xml->getAttribute('protocolSupportEnumeration')));

        if ($xml->hasAttribute('errorURL')) {
            $this->setErrorURL($xml->getAttribute('errorURL'));
        }

        $this->setExtensions(Extensions::getList($xml));

        foreach (Utils::xpQuery($xml, './saml_metadata:KeyDescriptor') as $kd) {
            $this->addKeyDescriptor(new KeyDescriptor($kd));
        }

        $organization = Utils::xpQuery($xml, './saml_metadata:Organization');
        if (count($organization) > 1) {
            throw new \Exception('More than one Organization in the entity.');
        } elseif (!empty($organization)) {
            $this->setOrganization(new Organization($organization[0]));
        }

        foreach (Utils::xpQuery($xml, './saml_metadata:ContactPerson') as $cp) {
            $this->addContactPerson(new ContactPerson($cp));
        }
    }


    /**
     * Collect the value of the ID-property
     * @return string|null
     */
    public function getID()
    {
        return $this->ID;
    }


    /**
     * Set the value of the ID-property
     * @param string|null $Id
     * @return void
     */
    public function setID($Id = null)
    {
        Assert::nullOrString($Id);
        $this->ID = $Id;
    }


    /**
     * Collect the value of the validUntil-property
     * @return int|null
     */
    public function getValidUntil()
    {
        return $this->validUntil;
    }


    /**
     * Set the value of the validUntil-property
     * @param int|null $validUntil
     * @return void
     */
    public function setValidUntil($validUntil = null)
    {
        Assert::nullOrInteger($validUntil);
        $this->validUntil = $validUntil;
    }


    /**
     * Collect the value of the cacheDuration-property
     * @return string|null
     */
    public function getCacheDuration()
    {
        return $this->cacheDuration;
    }


    /**
     * Set the value of the cacheDuration-property
     * @param string|null $cacheDuration
     * @return void
     */
    public function setCacheDuration($cacheDuration = null)
    {
        Assert::nullOrString($cacheDuration);
        $this->cacheDuration = $cacheDuration;
    }


    /**
     * Collect the value of the Extensions-property
     * @return \SAML2\XML\Chunk[]
     */
    public function getExtensions()
    {
        return $this->Extensions;
    }


    /**
     * Set the value of the Extensions-property
     * @param array $extensions
     * @return void
     */
    public function setExtensions(array $extensions)
    {
        $this->Extensions = $extensions;
    }


    /**
     * Add an Extension.
     *
     * @param \SAML2\XML\Chunk $extensions The Extensions
     * @return void
     */
    public function addExtension(Extensions $extension)
    {
        $this->Extensions[] = $extension;
    }


    /**
     * Set the value of the errorURL-property
     * @param string|null $errorURL
     * @return void
     */
    public function setErrorURL($errorURL = null)
    {
        Assert::nullOrString($errorURL);
        if (!is_null($errorURL) && !filter_var($errorURL, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('RoleDescriptor errorURL is not a valid URL.');
        }
        $this->errorURL = $errorURL;
    }


    /**
     * Collect the value of the errorURL-property
     * @return string|null
     */
    public function getErrorURL()
    {
        return $this->errorURL;
    }


    /**
     * Collect the value of the ProtocolSupportEnumeration-property
     * @return string[]
     */
    public function getProtocolSupportEnumeration()
    {
        return $this->protocolSupportEnumeration;
    }


    /**
     * Set the value of the ProtocolSupportEnumeration-property
     * @param array $protocols
     * @return void
     */
    public function setProtocolSupportEnumeration(array $protocols)
    {
        $this->protocolSupportEnumeration = $protocols;
    }


    /**
     * Add the value to the ProtocolSupportEnumeration-property
     * @param string $protocol
     * @return void
     */
    public function addProtocolSupportEnumeration($protocol)
    {
        $this->protocolSupportEnumeration[] = $protocol;
    }


    /**
     * Collect the value of the Organization-property
     * @return \SAML2\XML\md\Organization
     */
    public function getOrganization()
    {
        return $this->Organization;
    }


    /**
     * Set the value of the Organization-property
     * @param \SAML2\XML\md\Organization|null $organization
     * @return void
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->Organization = $organization;
    }


    /**
     * Collect the value of the ContactPerson-property
     * @return \SAML2\XML\md\ContactPerson[]
     */
    public function getContactPerson()
    {
        return $this->ContactPerson;
    }


    /**
     * Set the value of the ContactPerson-property
     * @param array $contactPerson
     * @return void
     */
    public function setContactPerson(array $contactPerson)
    {
        $this->ContactPerson = $contactPerson;
    }


    /**
     * Add the value to the ContactPerson-property
     * @param \SAML2\XML\md\ContactPerson $contactPerson
     * @return void
     */
    public function addContactPerson(ContactPerson $contactPerson)
    {
        $this->ContactPerson[] = $contactPerson;
    }


    /**
     * Collect the value of the KeyDescriptor-property
     * @return \SAML2\XML\md\KeyDescriptor[]
     */
    public function getKeyDescriptor()
    {
        return $this->KeyDescriptor;
    }


    /**
     * Set the value of the KeyDescriptor-property
     * @param array $keyDescriptor
     * @return void
     */
    public function setKeyDescriptor(array $keyDescriptor)
    {
        $this->KeyDescriptor = $keyDescriptor;
    }


    /**
     * Add the value to the KeyDescriptor-property
     * @param \SAML2\XML\md\KeyDescriptor $keyDescriptor
     * @return void
     */
    public function addKeyDescriptor(KeyDescriptor $keyDescriptor)
    {
        $this->KeyDescriptor[] = $keyDescriptor;
    }


    /**
     * Add this RoleDescriptor to an EntityDescriptor.
     *
     * @param \DOMElement $parent The EntityDescriptor we should append this endpoint to.
     * @return \DOMElement
     */
    protected function toXML(\DOMElement $parent)
    {
        Assert::nullOrString($this->getID());
        Assert::nullOrInteger($this->getValidUntil());
        Assert::nullOrString($this->getCacheDuration());
        Assert::isArray($this->getProtocolSupportEnumeration());
        Assert::nullOrString($this->getErrorURL());
        Assert::isArray($this->getExtensions());
        Assert::isArray($this->getKeyDescriptor());
        Assert::nullOrIsInstanceOf($this->getOrganization(), Organization::class);
        Assert::isArray($this->getContactPerson());

        $e = $parent->ownerDocument->createElementNS(Constants::NS_MD, $this->elementName);
        $parent->appendChild($e);

        if ($this->getID() !== null) {
            $e->setAttribute('ID', $this->getID());
        }

        if ($this->getValidUntil() !== null) {
            $e->setAttribute('validUntil', gmdate('Y-m-d\TH:i:s\Z', $this->getValidUntil()));
        }

        if ($this->getCacheDuration() !== null) {
            $e->setAttribute('cacheDuration', $this->getCacheDuration());
        }

        $e->setAttribute('protocolSupportEnumeration', implode(' ', $this->getProtocolSupportEnumeration()));

        if ($this->getErrorURL() !== null) {
            $e->setAttribute('errorURL', $this->getErrorURL());
        }

        Extensions::addList($e, $this->getExtensions());

        foreach ($this->getKeyDescriptor() as $kd) {
            $kd->toXML($e);
        }

        if ($this->getOrganization() !== null) {
            $this->getOrganization()->toXML($e);
        }

        foreach ($this->getContactPerson() as $cp) {
            $cp->toXML($e);
        }

        return $e;
    }
}
