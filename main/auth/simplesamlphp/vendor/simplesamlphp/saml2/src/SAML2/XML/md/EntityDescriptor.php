<?php

namespace SAML2\XML\md;

use SAML2\Constants;
use SAML2\DOMDocumentFactory;
use SAML2\SignedElementHelper;
use SAML2\Utils;
use SAML2\XML\Chunk;
use SAML2\XML\md\AffiliationDescriptor;
use SAML2\XML\md\Organization;
use Webmozart\Assert\Assert;

/**
 * Class representing SAML 2 EntityDescriptor element.
 *
 * @package SimpleSAMLphp
 */
class EntityDescriptor extends SignedElementHelper
{
    /**
     * The entityID this EntityDescriptor represents.
     *
     * @var string
     */
    public $entityID;

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
     * Extensions on this element.
     *
     * Array of extension elements.
     *
     * @var array
     */
    public $Extensions = [];

    /**
     * Array with all roles for this entity.
     *
     * Array of \SAML2\XML\md\RoleDescriptor objects (and subclasses of RoleDescriptor).
     *
     * @var (\SAML2\XML\md\UnknownRoleDescriptor|\SAML2\XML\md\IDPSSODescriptor|\SAML2\XML\md\SPSSODescriptor|\SAML2\XML\md\AuthnAuthorityDescriptor|\SAML2\XML\md\AttributeAuthorityDescriptor|\SAML2\XML\md\PDPDescriptor)[]
     */
    public $RoleDescriptor = [];

    /**
     * AffiliationDescriptor of this entity.
     *
     * @var \SAML2\XML\md\AffiliationDescriptor|null
     */
    public $AffiliationDescriptor = null;

    /**
     * Organization of this entity.
     *
     * @var \SAML2\XML\md\Organization|null
     */
    public $Organization = null;

    /**
     * ContactPerson elements for this entity.
     *
     * @var \SAML2\XML\md\ContactPerson[]
     */
    public $ContactPerson = [];

    /**
     * AdditionalMetadataLocation elements for this entity.
     *
     * @var \SAML2\XML\md\AdditionalMetadataLocation[]
     */
    public $AdditionalMetadataLocation = [];


    /**
     * Initialize an EntitiyDescriptor.
     *
     * @param \DOMElement|null $xml The XML element we should load.
     * @throws \Exception
     */
    public function __construct(\DOMElement $xml = null)
    {
        parent::__construct($xml);

        if ($xml === null) {
            return;
        }

        if (!$xml->hasAttribute('entityID')) {
            throw new \Exception('Missing required attribute entityID on EntityDescriptor.');
        }
        $this->setEntityID($xml->getAttribute('entityID'));

        if ($xml->hasAttribute('ID')) {
            $this->setID($xml->getAttribute('ID'));
        }
        if ($xml->hasAttribute('validUntil')) {
            $this->setValidUntil(Utils::xsDateTimeToTimestamp($xml->getAttribute('validUntil')));
        }
        if ($xml->hasAttribute('cacheDuration')) {
            $this->setCacheDuration($xml->getAttribute('cacheDuration'));
        }

        $this->setExtensions(Extensions::getList($xml));

        for ($node = $xml->firstChild; $node !== null; $node = $node->nextSibling) {
            if (!($node instanceof \DOMElement)) {
                continue;
            }

            if ($node->namespaceURI !== Constants::NS_MD) {
                continue;
            }

            switch ($node->localName) {
                case 'RoleDescriptor':
                    $this->addRoleDescriptor(new UnknownRoleDescriptor($node));
                    break;
                case 'IDPSSODescriptor':
                    $this->addRoleDescriptor(new IDPSSODescriptor($node));
                    break;
                case 'SPSSODescriptor':
                    $this->addRoleDescriptor(new SPSSODescriptor($node));
                    break;
                case 'AuthnAuthorityDescriptor':
                    $this->addRoleDescriptor(new AuthnAuthorityDescriptor($node));
                    break;
                case 'AttributeAuthorityDescriptor':
                    $this->addRoleDescriptor(new AttributeAuthorityDescriptor($node));
                    break;
                case 'PDPDescriptor':
                    $this->addRoleDescriptor(new PDPDescriptor($node));
                    break;
            }
        }

        $affiliationDescriptor = Utils::xpQuery($xml, './saml_metadata:AffiliationDescriptor');
        if (count($affiliationDescriptor) > 1) {
            throw new \Exception('More than one AffiliationDescriptor in the entity.');
        } elseif (!empty($affiliationDescriptor)) {
            $this->setAffiliationDescriptor(new AffiliationDescriptor($affiliationDescriptor[0]));
        }

        $roleDescriptor = $this->getRoleDescriptor();
        if (empty($roleDescriptor) && is_null($this->getAffiliationDescriptor())) {
            throw new \Exception('Must have either one of the RoleDescriptors or an AffiliationDescriptor in EntityDescriptor.');
        } elseif (!empty($roleDescriptor) && !is_null($this->getAffiliationDescriptor())) {
            throw new \Exception('AffiliationDescriptor cannot be combined with other RoleDescriptor elements in EntityDescriptor.');
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

        foreach (Utils::xpQuery($xml, './saml_metadata:AdditionalMetadataLocation') as $aml) {
            $this->addAdditionalMetadataLocation(new AdditionalMetadataLocation($aml));
        }
    }


    /**
     * Collect the value of the entityID-property
     * @return string
     */
    public function getEntityID()
    {
        return $this->entityID;
    }


    /**
     * Set the value of the entityID-property
     * @param string|null $entityId
     * @return void
     */
    public function setEntityID($entityId)
    {
        Assert::nullOrString($entityId);
        $this->entityID = $entityId;
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
     * Collect the value of the RoleDescriptor-property
     * @return \SAML2\XML\md\RoleDescriptor[]
     */
    public function getRoleDescriptor()
    {
        return $this->RoleDescriptor;
    }


    /**
     * Set the value of the RoleDescriptor-property
     * @param array $roleDescriptor
     * @return void
     */
    public function setRoleDescriptor(array $roleDescriptor)
    {
        $this->RoleDescriptor = $roleDescriptor;
    }


    /**
     * Add the value to the RoleDescriptor-property
     * @param \SAML2\XML\md\RoleDescriptor $roleDescriptor
     * @return void
     */
    public function addRoleDescriptor(RoleDescriptor $roleDescriptor)
    {
        $this->RoleDescriptor[] = $roleDescriptor;
    }


    /**
     * Collect the value of the AffiliationDescriptor-property
     * @return \SAML2\XML\md\AffiliationDescriptor|null
     */
    public function getAffiliationDescriptor()
    {
        return $this->AffiliationDescriptor;
    }


    /**
     * Set the value of the AffliationDescriptor-property
     * @param \SAML2\XML\md\AffiliationDescriptor|null $affiliationDescriptor
     * @return void
     */
    public function setAffiliationDescriptor(AffiliationDescriptor $affiliationDescriptor = null)
    {
        $this->AffiliationDescriptor = $affiliationDescriptor;
    }


    /**
     * Collect the value of the Organization-property
     * @return \SAML2\XML\md\Organization|null
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
     * Collect the value of the AdditionalMetadataLocation-property
     * @return \SAML2\XML\md\AdditionalMetadataLocation[]
     */
    public function getAdditionalMetadataLocation()
    {
        return $this->AdditionalMetadataLocation;
    }


    /**
     * Set the value of the AdditionalMetadataLocation-property
     * @param array $additionalMetadataLocation
     * @return void
     */
    public function setAdditionalMetadataLocation(array $additionalMetadataLocation)
    {
        $this->AdditionalMetadataLocation = $additionalMetadataLocation;
    }


    /**
     * Add the value to the AdditionalMetadataLocation-property
     * @param AdditionalMetadataLocation $additionalMetadataLocation
     * @return void
     */
    public function addAdditionalMetadataLocation(AdditionalMetadataLocation $additionalMetadataLocation)
    {
        $this->AdditionalMetadataLocation[] = $additionalMetadataLocation;
    }


    /**
     * Create this EntityDescriptor.
     *
     * @param \DOMElement|null $parent The EntitiesDescriptor we should append this EntityDescriptor to.
     * @return \DOMElement
     */
    public function toXML(\DOMElement $parent = null)
    {
        Assert::string($this->getEntityID());
        Assert::nullOrString($this->getID());
        Assert::nullOrInteger($this->getValidUntil());
        Assert::nullOrString($this->getCacheDuration());
        Assert::isArray($this->getExtensions());
        Assert::isArray($this->getRoleDescriptor());
        Assert::nullOrIsInstanceOf($this->getAffiliationDescriptor(), AffiliationDescriptor::class);
        Assert::nullOrIsInstanceOf($this->getOrganization(), Organization::class);
        Assert::isArray($this->getContactPerson());
        Assert::isArray($this->getAdditionalMetadataLocation());

        if ($parent === null) {
            $doc = DOMDocumentFactory::create();
            $e = $doc->createElementNS(Constants::NS_MD, 'md:EntityDescriptor');
            $doc->appendChild($e);
        } else {
            $e = $parent->ownerDocument->createElementNS(Constants::NS_MD, 'md:EntityDescriptor');
            $parent->appendChild($e);
        }

        $e->setAttribute('entityID', $this->getEntityID());

        if ($this->getID() !== null) {
            $e->setAttribute('ID', $this->getID());
        }

        if ($this->getValidUntil() !== null) {
            $e->setAttribute('validUntil', gmdate('Y-m-d\TH:i:s\Z', $this->getValidUntil()));
        }

        if ($this->getCacheDuration() !== null) {
            $e->setAttribute('cacheDuration', $this->getCacheDuration());
        }

        Extensions::addList($e, $this->getExtensions());

        /** @var \SAML2\XML\md\UnknownRoleDescriptor|\SAML2\XML\md\IDPSSODescriptor|\SAML2\XML\md\SPSSODescriptor|\SAML2\XML\md\AuthnAuthorityDescriptor|\SAML2\XML\md\AttributeAuthorityDescriptor|\SAML2\XML\md\PDPDescriptor $n */
        foreach ($this->getRoleDescriptor() as $n) {
            $n->toXML($e);
        }

        if ($this->getAffiliationDescriptor() !== null) {
            $this->getAffiliationDescriptor()->toXML($e);
        }

        if ($this->getOrganization() !== null) {
            $this->getOrganization()->toXML($e);
        }

        foreach ($this->getContactPerson() as $cp) {
            $cp->toXML($e);
        }

        foreach ($this->getAdditionalMetadataLocation() as $n) {
            $n->toXML($e);
        }

        $this->signElement($e, $e->firstChild);

        return $e;
    }
}
