<?php

namespace SAML2\XML\saml;

use SAML2\Constants;
use SAML2\Utils;
use Webmozart\Assert\Assert;

/**
 * Class representing SAML 2 SubjectConfirmation element.
 *
 * @package SimpleSAMLphp
 */
class SubjectConfirmation
{
    /**
     * The method we can use to verify this Subject.
     *
     * @var string
     */
    public $Method;

    /**
     * The NameID of the entity that can use this element to verify the Subject.
     *
     * @var \SAML2\XML\saml\NameID|null
     */
    public $NameID;

    /**
     * SubjectConfirmationData element with extra data for verification of the Subject.
     *
     * @var \SAML2\XML\saml\SubjectConfirmationData|null
     */
    public $SubjectConfirmationData;


    /**
     * Initialize (and parse? a SubjectConfirmation element.
     *
     * @param \DOMElement|null $xml The XML element we should load.
     * @throws \Exception
     */
    public function __construct(\DOMElement $xml = null)
    {
        if ($xml === null) {
            return;
        }

        if (!$xml->hasAttribute('Method')) {
            throw new \Exception('SubjectConfirmation element without Method attribute.');
        }
        $this->setMethod($xml->getAttribute('Method'));

        $nid = Utils::xpQuery($xml, './saml_assertion:NameID');
        if (count($nid) > 1) {
            throw new \Exception('More than one NameID in a SubjectConfirmation element.');
        } elseif (!empty($nid)) {
            $this->setNameID(new NameID($nid[0]));
        }

        $scd = Utils::xpQuery($xml, './saml_assertion:SubjectConfirmationData');
        if (count($scd) > 1) {
            throw new \Exception('More than one SubjectConfirmationData child in a SubjectConfirmation element.');
        } elseif (!empty($scd)) {
            $this->setSubjectConfirmationData(new SubjectConfirmationData($scd[0]));
        }
    }


    /**
     * Collect the value of the Method-property
     * @return string
     */
    public function getMethod()
    {
        return $this->Method;
    }


    /**
     * Set the value of the Method-property
     * @param string $method
     * @return void
     */
    public function setMethod($method)
    {
        Assert::string($method);
        $this->Method = $method;
    }


    /**
     * Collect the value of the NameID-property
     * @return \SAML2\XML\saml\NameID
     */
    public function getNameID()
    {
        return $this->NameID;
    }


    /**
     * Set the value of the NameID-property
     * @param \SAML2\XML\saml\NameID $nameId
     * @return void
     */
    public function setNameID(NameID $nameId)
    {
        $this->NameID = $nameId;
    }


    /**
     * Collect the value of the SubjectConfirmationData-property
     * @return \SAML2\XML\saml\SubjectConfirmationData|null
     */
    public function getSubjectConfirmationData()
    {
        return $this->SubjectConfirmationData;
    }


    /**
     * Set the value of the SubjectConfirmationData-property
     * @param \SAML2\XML\saml\SubjectConfirmationData|null $subjectConfirmationData
     * @return void
     */
    public function setSubjectConfirmationData($subjectConfirmationData = null)
    {
        $this->SubjectConfirmationData = $subjectConfirmationData;
    }


    /**
     * Convert this element to XML.
     *
     * @param  \DOMElement $parent The parent element we should append this element to.
     * @return \DOMElement This element, as XML.
     */
    public function toXML(\DOMElement $parent)
    {
        Assert::string($this->getMethod());
        Assert::nullOrIsInstanceOf($this->getNameID(), NameID::class);
        Assert::nullOrIsInstanceOf($this->getSubjectConfirmationData(), SubjectConfirmationData::class);

        $e = $parent->ownerDocument->createElementNS(Constants::NS_SAML, 'saml:SubjectConfirmation');
        $parent->appendChild($e);

        $e->setAttribute('Method', $this->getMethod());

        if ($this->getNameID() !== null) {
            $this->getNameID()->toXML($e);
        }
        if ($this->getSubjectConfirmationData() !== null) {
            $this->getSubjectConfirmationData()->toXML($e);
        }

        return $e;
    }
}
