<?php

namespace SAML2;

use RobRichards\XMLSecLibs\XMLSecEnc;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\XML\saml\NameID;
use SAML2\XML\saml\SubjectConfirmation;
use SAML2\Exception\InvalidArgumentException;
use Webmozart\Assert\Assert;

/**
 * Class for SAML 2 authentication request messages.
 *
 * @package SimpleSAMLphp
 */
class AuthnRequest extends Request
{
    /**
     * The options for what type of name identifier should be returned.
     *
     * @var array
     */
    private $nameIdPolicy;

    /**
     * Whether the Identity Provider must authenticate the user again.
     *
     * @var bool
     */
    private $forceAuthn;

    /**
     * Optional ProviderID attribute
     *
     * @var string
     */
    private $ProviderName;

    /**
     * Set to true if this request is passive.
     *
     * @var bool
     */
    private $isPassive;

    /**
     * The list of providerIDs in this request's scoping element
     *
     * @var array
     */
    private $IDPList = [];

    /**
     * The ProxyCount in this request's scoping element
     *
     * @var int
     */
    private $ProxyCount = null;

    /**
     * The RequesterID list in this request's scoping element
     *
     * @var array
     */

    private $RequesterID = [];

    /**
     * The URL of the asertion consumer service where the response should be delivered.
     *
     * @var string|null
     */
    private $assertionConsumerServiceURL;

    /**
     * What binding should be used when sending the response.
     *
     * @var string|null
     */
    private $protocolBinding;

    /**
     * The index of the AttributeConsumingService.
     *
     * @var int|null
     */
    private $attributeConsumingServiceIndex;

    /**
     * The index of the AssertionConsumerService.
     *
     * @var int|null
     */
    private $assertionConsumerServiceIndex;

    /**
     * What authentication context was requested.
     *
     * Array with the following elements.
     * - AuthnContextClassRef (required)
     * - Comparison (optinal)
     *
     * @var array
     */
    private $requestedAuthnContext;

    /**
     * Audiences to send in the request.
     *
     * @var array
     */
    private $audiences = [];

    /**
     * @var \SAML2\XML\saml\SubjectConfirmation[]
     */
    private $subjectConfirmation = [];

    /**
     * @var string
     */
    private $encryptedNameId;

    /**
     * @var \SAML2\XML\saml\NameID
     */
    private $nameId;


    /**
     * Constructor for SAML 2 authentication request messages.
     *
     * @param \DOMElement|null $xml The input message.
     * @throws \Exception
     */
    public function __construct(\DOMElement $xml = null)
    {
        parent::__construct('AuthnRequest', $xml);

        $this->nameIdPolicy = [];
        $this->forceAuthn = false;
        $this->isPassive = false;

        if ($xml === null) {
            return;
        }

        $this->forceAuthn = Utils::parseBoolean($xml, 'ForceAuthn', false);
        $this->isPassive = Utils::parseBoolean($xml, 'IsPassive', false);

        if ($xml->hasAttribute('AssertionConsumerServiceURL')) {
            $this->assertionConsumerServiceURL = $xml->getAttribute('AssertionConsumerServiceURL');
        }

        if ($xml->hasAttribute('ProtocolBinding')) {
            $this->protocolBinding = $xml->getAttribute('ProtocolBinding');
        }

        if ($xml->hasAttribute('AttributeConsumingServiceIndex')) {
            $this->attributeConsumingServiceIndex = (int) $xml->getAttribute('AttributeConsumingServiceIndex');
        }

        if ($xml->hasAttribute('AssertionConsumerServiceIndex')) {
            $this->assertionConsumerServiceIndex = (int) $xml->getAttribute('AssertionConsumerServiceIndex');
        }

        if ($xml->hasAttribute('ProviderName')) {
            $this->ProviderName = $xml->getAttribute('ProviderName');
        }

        $this->parseSubject($xml);
        $this->parseNameIdPolicy($xml);
        $this->parseRequestedAuthnContext($xml);
        $this->parseScoping($xml);
        $this->parseConditions($xml);
    }


    /**
     * @param $xml
     * @throws \Exception
     * @return void
     */
    private function parseSubject(\DOMElement $xml)
    {
        $subject = Utils::xpQuery($xml, './saml_assertion:Subject');
        if (empty($subject)) {
            return;
        }

        if (count($subject) > 1) {
            throw new \Exception('More than one <saml:Subject> in <saml:AuthnRequest>.');
        }
        $subject = $subject[0];

        $nameId = Utils::xpQuery(
            $subject,
            './saml_assertion:NameID | ./saml_assertion:EncryptedID/xenc:EncryptedData'
        );
        if (empty($nameId)) {
            throw new \Exception('Missing <saml:NameID> or <saml:EncryptedID> in <saml:Subject>.');
        } elseif (count($nameId) > 1) {
            throw new \Exception('More than one <saml:NameID> or <saml:EncryptedID> in <saml:Subject>.');
        }
        $nameId = $nameId[0];
        if ($nameId->localName === 'EncryptedData') {
            /* The NameID element is encrypted. */
            $this->encryptedNameId = $nameId;
        } else {
            $this->nameId = new NameID($nameId);
        }

        $subjectConfirmation = Utils::xpQuery($subject, './saml_assertion:SubjectConfirmation');
        foreach ($subjectConfirmation as $sc) {
            $this->subjectConfirmation[] = new SubjectConfirmation($sc);
        }
    }


    /**
     * @param \DOMElement $xml
     * @throws \Exception
     * @return void
     */
    protected function parseNameIdPolicy(\DOMElement $xml)
    {
        $nameIdPolicy = Utils::xpQuery($xml, './saml_protocol:NameIDPolicy');
        if (empty($nameIdPolicy)) {
            return;
        }

        $nameIdPolicy = $nameIdPolicy[0];
        if ($nameIdPolicy->hasAttribute('Format')) {
            $this->nameIdPolicy['Format'] = $nameIdPolicy->getAttribute('Format');
        }
        if ($nameIdPolicy->hasAttribute('SPNameQualifier')) {
            $this->nameIdPolicy['SPNameQualifier'] = $nameIdPolicy->getAttribute('SPNameQualifier');
        }
        if ($nameIdPolicy->hasAttribute('AllowCreate')) {
            $this->nameIdPolicy['AllowCreate'] = Utils::parseBoolean($nameIdPolicy, 'AllowCreate', false);
        }
    }


    /**
     * @param \DOMElement $xml
     * @return void
     */
    protected function parseRequestedAuthnContext(\DOMElement $xml)
    {
        $requestedAuthnContext = Utils::xpQuery($xml, './saml_protocol:RequestedAuthnContext');
        if (empty($requestedAuthnContext)) {
            return;
        }

        $requestedAuthnContext = $requestedAuthnContext[0];

        $rac = [
            'AuthnContextClassRef' => [],
            'Comparison'           => Constants::COMPARISON_EXACT,
        ];

        $accr = Utils::xpQuery($requestedAuthnContext, './saml_assertion:AuthnContextClassRef');
        foreach ($accr as $i) {
            $rac['AuthnContextClassRef'][] = trim($i->textContent);
        }

        if ($requestedAuthnContext->hasAttribute('Comparison')) {
            $rac['Comparison'] = $requestedAuthnContext->getAttribute('Comparison');
        }

        $this->requestedAuthnContext = $rac;
    }


    /**
     * @param \DOMElement $xml
     * @throws \Exception
     * @return void
     */
    protected function parseScoping(\DOMElement $xml)
    {
        $scoping = Utils::xpQuery($xml, './saml_protocol:Scoping');
        if (empty($scoping)) {
            return;
        }

        $scoping = $scoping[0];

        if ($scoping->hasAttribute('ProxyCount')) {
            $this->ProxyCount = (int) $scoping->getAttribute('ProxyCount');
        }
        $idpEntries = Utils::xpQuery($scoping, './saml_protocol:IDPList/saml_protocol:IDPEntry');

        foreach ($idpEntries as $idpEntry) {
            if (!$idpEntry->hasAttribute('ProviderID')) {
                throw new \Exception("Could not get ProviderID from Scoping/IDPEntry element in AuthnRequest object");
            }
            $this->IDPList[] = $idpEntry->getAttribute('ProviderID');
        }

        $requesterIDs = Utils::xpQuery($scoping, './saml_protocol:RequesterID');
        foreach ($requesterIDs as $requesterID) {
            $this->RequesterID[] = trim($requesterID->textContent);
        }
    }


    /**
     * @param \DOMElement $xml
     * @return void
     */
    protected function parseConditions(\DOMElement $xml)
    {
        $conditions = Utils::xpQuery($xml, './saml_assertion:Conditions');
        if (empty($conditions)) {
            return;
        }
        $conditions = $conditions[0];

        $ar = Utils::xpQuery($conditions, './saml_assertion:AudienceRestriction');
        if (empty($ar)) {
            return;
        }
        $ar = $ar[0];

        $audiences = Utils::xpQuery($ar, './saml_assertion:Audience');
        $this->audiences = array();
        foreach ($audiences as $a) {
            $this->audiences[] = trim($a->textContent);
        }
    }


    /**
     * Retrieve the NameIdPolicy.
     *
     * @see \SAML2\AuthnRequest::setNameIdPolicy()
     * @return array The NameIdPolicy.
     */
    public function getNameIdPolicy()
    {
        return $this->nameIdPolicy;
    }


    /**
     * Set the NameIDPolicy.
     *
     * This function accepts an array with the following options:
     *  - 'Format' (string)
     *  - 'SPNameQualifier' (string)
     *  - 'AllowCreate' (bool)
     *
     * @param array $nameIdPolicy The NameIDPolicy.
     * @return void
     */
    public function setNameIdPolicy(array $nameIdPolicy)
    {
        if (isset($nameIdPolicy['Format']) && !is_string($nameIdPolicy['Format'])) {
            throw InvalidArgumentException::invalidType('string', $nameIdPolicy['Format']);
        }
        if (isset($nameIdPolicy['SPNameQualifier']) && !is_string($nameIdPolicy['SPNameQualifier'])) {
            throw InvalidArgumentException::invalidType('string', $nameIdPolicy['SPNameQualifier']);
        }
        if (isset($nameIdPolicy['AllowCreate']) && !is_bool($nameIdPolicy['AllowCreate'])) {
            throw InvalidArgumentException::invalidType('bool', $nameIdPolicy['AllowCreate']);
        }

        $this->nameIdPolicy = $nameIdPolicy;
    }


    /**
     * Retrieve the value of the ForceAuthn attribute.
     *
     * @return bool The ForceAuthn attribute.
     */
    public function getForceAuthn()
    {
        return $this->forceAuthn;
    }


    /**
     * Set the value of the ForceAuthn attribute.
     *
     * @param bool $forceAuthn The ForceAuthn attribute.
     * @return void
     */
    public function setForceAuthn($forceAuthn)
    {
        Assert::boolean($forceAuthn);

        $this->forceAuthn = $forceAuthn;
    }


    /**
     * Retrieve the value of the ProviderName attribute.
     *
     * @return string The ProviderName attribute.
     */
    public function getProviderName()
    {
        return $this->ProviderName;
    }


    /**
     * Set the value of the ProviderName attribute.
     *
     * @param string $ProviderName The ProviderName attribute.
     * @return void
     */
    public function setProviderName($ProviderName)
    {
        Assert::string($ProviderName);

        $this->ProviderName = $ProviderName;
    }


    /**
     * Retrieve the value of the IsPassive attribute.
     *
     * @return bool The IsPassive attribute.
     */
    public function getIsPassive()
    {
        return $this->isPassive;
    }


    /**
     * Set the value of the IsPassive attribute.
     *
     * @param bool $isPassive The IsPassive attribute.
     * @return void
     */
    public function setIsPassive($isPassive)
    {
        Assert::boolean($isPassive);

        $this->isPassive = $isPassive;
    }


    /**
     * Retrieve the audiences from the request.
     * This may be null, in which case no audience is included.
     *
     * @return array The audiences.
     */
    public function getAudiences()
    {
        return $this->audiences;
    }


    /**
     * Set the audiences to send in the request.
     * This may be null, in which case no audience will be sent.
     *
     * @param array|null $audiences The audiences.
     * @return void
     */
    public function setAudiences(array $audiences)
    {
        $this->audiences = $audiences;
    }


    /**
     * This function sets the scoping for the request.
     * See Core 3.4.1.2 for the definition of scoping.
     * Currently we support an IDPList of idpEntries.
     *
     * Each idpEntries consists of an array, containing
     * keys (mapped to attributes) and corresponding values.
     * Allowed attributes: Loc, Name, ProviderID.
     *
     * For backward compatibility, an idpEntries can also
     * be a string instead of an array, where each string
     * is mapped to the value of attribute ProviderID.
     *
     * @param array $IDPList List of idpEntries to scope the request to.
     * @return void
     */
    public function setIDPList(array $IDPList)
    {
        $this->IDPList = $IDPList;
    }


    /**
     * This function retrieves the list of providerIDs from this authentication request.
     * Currently we only support a list of ipd ientity id's.
     *
     * @return array List of idp EntityIDs from the request
     */
    public function getIDPList()
    {
        return $this->IDPList;
    }


    /**
     * @param int $ProxyCount
     * @return void
     */
    public function setProxyCount($ProxyCount)
    {
        Assert::integer($ProxyCount);
        $this->ProxyCount = $ProxyCount;
    }


    /**
     * @return int|null
     */
    public function getProxyCount()
    {
        return $this->ProxyCount;
    }


    /**
     * @param array $RequesterID
     * @return void
     */
    public function setRequesterID(array $RequesterID)
    {
        $this->RequesterID = $RequesterID;
    }


    /**
     * @return array
     */
    public function getRequesterID()
    {
        return $this->RequesterID;
    }


    /**
     * Retrieve the value of the AssertionConsumerServiceURL attribute.
     *
     * @return string|null The AssertionConsumerServiceURL attribute.
     */
    public function getAssertionConsumerServiceURL()
    {
        return $this->assertionConsumerServiceURL;
    }


    /**
     * Set the value of the AssertionConsumerServiceURL attribute.
     *
     * @param string|null $assertionConsumerServiceURL The AssertionConsumerServiceURL attribute.
     * @return void
     */
    public function setAssertionConsumerServiceURL($assertionConsumerServiceURL)
    {
        Assert::nullOrString($assertionConsumerServiceURL);

        $this->assertionConsumerServiceURL = $assertionConsumerServiceURL;
    }


    /**
     * Retrieve the value of the ProtocolBinding attribute.
     *
     * @return string|null The ProtocolBinding attribute.
     */
    public function getProtocolBinding()
    {
        return $this->protocolBinding;
    }


    /**
     * Set the value of the ProtocolBinding attribute.
     *
     * @param string $protocolBinding The ProtocolBinding attribute.
     * @return void
     */
    public function setProtocolBinding($protocolBinding)
    {
        Assert::nullOrString($protocolBinding);

        $this->protocolBinding = $protocolBinding;
    }


    /**
     * Retrieve the value of the AttributeConsumingServiceIndex attribute.
     *
     * @return int|null The AttributeConsumingServiceIndex attribute.
     */
    public function getAttributeConsumingServiceIndex()
    {
        return $this->attributeConsumingServiceIndex;
    }


    /**
     * Set the value of the AttributeConsumingServiceIndex attribute.
     *
     * @param int|null $attributeConsumingServiceIndex The AttributeConsumingServiceIndex attribute.
     * @return void
     */
    public function setAttributeConsumingServiceIndex($attributeConsumingServiceIndex)
    {
        Assert::nullOrInteger($attributeConsumingServiceIndex);

        $this->attributeConsumingServiceIndex = $attributeConsumingServiceIndex;
    }


    /**
     * Retrieve the value of the AssertionConsumerServiceIndex attribute.
     *
     * @return int|null The AssertionConsumerServiceIndex attribute.
     */
    public function getAssertionConsumerServiceIndex()
    {
        return $this->assertionConsumerServiceIndex;
    }


    /**
     * Set the value of the AssertionConsumerServiceIndex attribute.
     *
     * @param int|null $assertionConsumerServiceIndex The AssertionConsumerServiceIndex attribute.
     * @return void
     */
    public function setAssertionConsumerServiceIndex($assertionConsumerServiceIndex)
    {
        Assert::nullOrString($assertionConsumerServiceIndex);

        $this->assertionConsumerServiceIndex = $assertionConsumerServiceIndex;
    }


    /**
     * Retrieve the RequestedAuthnContext.
     *
     * @return array|null The RequestedAuthnContext.
     */
    public function getRequestedAuthnContext()
    {
        return $this->requestedAuthnContext;
    }


    /**
     * Set the RequestedAuthnContext.
     *
     * @param array $requestedAuthnContext The RequestedAuthnContext.
     * @return void
     */
    public function setRequestedAuthnContext(array $requestedAuthnContext)
    {
        $this->requestedAuthnContext = $requestedAuthnContext;
    }


    /**
     * Retrieve the NameId of the subject in the assertion.
     *
     * @throws \Exception
     * @return \SAML2\XML\saml\NameID|null The name identifier of the assertion.
     */
    public function getNameId()
    {
        if ($this->encryptedNameId !== null) {
            throw new \Exception('Attempted to retrieve encrypted NameID without decrypting it first.');
        }

        return $this->nameId;
    }


    /**
     * Set the NameId of the subject in the assertion.
     *
     * @param \SAML2\XML\saml\NameID|null $nameId The name identifier of the assertion.
     * @return void
     */
    public function setNameId($nameId)
    {
        Assert::true(is_array($nameId) || is_null($nameId) || $nameId instanceof NameID);

        if (is_array($nameId)) {
            $nameId = NameID::fromArray($nameId);
        }
        $this->nameId = $nameId;
    }


    /**
     * Encrypt the NameID in the AuthnRequest.
     *
     * @param XMLSecurityKey $key The encryption key.
     * @return void
     */
    public function encryptNameId(XMLSecurityKey $key)
    {
        /* First create a XML representation of the NameID. */
        $doc  = new \DOMDocument();
        $root = $doc->createElement('root');
        $doc->appendChild($root);
        $this->nameId->toXML($root);
        $nameId = $root->firstChild;

        Utils::getContainer()->debugMessage($nameId, 'encrypt');

        /* Encrypt the NameID. */
        $enc = new XMLSecEnc();
        $enc->setNode($nameId);
        // @codingStandardsIgnoreStart
        $enc->type = XMLSecEnc::Element;
        // @codingStandardsIgnoreEnd

        $symmetricKey = new XMLSecurityKey(XMLSecurityKey::AES128_CBC);
        $symmetricKey->generateSessionKey();
        $enc->encryptKey($key, $symmetricKey);

        $this->encryptedNameId = $enc->encryptNode($symmetricKey);
        $this->nameId = null;
    }


    /**
     * Decrypt the NameId of the subject in the assertion.
     *
     * @param XMLSecurityKey $key       The decryption key.
     * @param array          $blacklist Blacklisted decryption algorithms.
     * @return void
     */
    public function decryptNameId(XMLSecurityKey $key, array $blacklist = [])
    {
        if ($this->encryptedNameId === null) {
            /* No NameID to decrypt. */
            return;
        }

        $nameId = Utils::decryptElement($this->encryptedNameId, $key, $blacklist);
        Utils::getContainer()->debugMessage($nameId, 'decrypt');
        $this->nameId = new NameID($nameId);

        $this->encryptedNameId = null;
    }


    /**
     * Retrieve the SubjectConfirmation elements we have in our Subject element.
     *
     * @return \SAML2\XML\saml\SubjectConfirmation[]
     */
    public function getSubjectConfirmation()
    {
        return $this->subjectConfirmation;
    }


    /**
     * Set the SubjectConfirmation elements that should be included in the assertion.
     *
     * @param array \SAML2\XML\saml\SubjectConfirmation[]
     * @return void
     */
    public function setSubjectConfirmation(array $subjectConfirmation)
    {
        $this->subjectConfirmation = $subjectConfirmation;
    }


    /**
     * Convert this authentication request to an XML element.
     *
     * @return \DOMElement This authentication request.
     */
    public function toUnsignedXML()
    {
        $root = parent::toUnsignedXML();

        if ($this->forceAuthn) {
            $root->setAttribute('ForceAuthn', 'true');
        }

        if ($this->ProviderName !== null) {
            $root->setAttribute('ProviderName', $this->ProviderName);
        }

        if ($this->isPassive) {
            $root->setAttribute('IsPassive', 'true');
        }

        if ($this->assertionConsumerServiceIndex !== null) {
            $root->setAttribute('AssertionConsumerServiceIndex', $this->assertionConsumerServiceIndex);
        } else {
            if ($this->assertionConsumerServiceURL !== null) {
                $root->setAttribute('AssertionConsumerServiceURL', $this->assertionConsumerServiceURL);
            }
            if ($this->protocolBinding !== null) {
                $root->setAttribute('ProtocolBinding', $this->protocolBinding);
            }
        }

        if ($this->attributeConsumingServiceIndex !== null) {
            $root->setAttribute('AttributeConsumingServiceIndex', $this->attributeConsumingServiceIndex);
        }

        $this->addSubject($root);

        if (!empty($this->nameIdPolicy)) {
            $nameIdPolicy = $this->document->createElementNS(Constants::NS_SAMLP, 'NameIDPolicy');
            if (array_key_exists('Format', $this->nameIdPolicy)) {
                $nameIdPolicy->setAttribute('Format', $this->nameIdPolicy['Format']);
            }
            if (array_key_exists('SPNameQualifier', $this->nameIdPolicy)) {
                $nameIdPolicy->setAttribute('SPNameQualifier', $this->nameIdPolicy['SPNameQualifier']);
            }
            if (array_key_exists('AllowCreate', $this->nameIdPolicy)) {
                $nameIdPolicy->setAttribute('AllowCreate', ($this->nameIdPolicy['AllowCreate']) ? 'true' : 'false');
            }
            $root->appendChild($nameIdPolicy);
        }

        $this->addConditions($root);

        $rac = $this->requestedAuthnContext;
        if (!empty($rac) && !empty($rac['AuthnContextClassRef'])) {
            $e = $this->document->createElementNS(Constants::NS_SAMLP, 'RequestedAuthnContext');
            $root->appendChild($e);
            if (isset($rac['Comparison']) && $rac['Comparison'] !== Constants::COMPARISON_EXACT) {
                $e->setAttribute('Comparison', $rac['Comparison']);
            }
            foreach ($rac['AuthnContextClassRef'] as $accr) {
                Utils::addString($e, Constants::NS_SAML, 'AuthnContextClassRef', $accr);
            }
        }

        if ($this->ProxyCount !== null || count($this->IDPList) > 0 || count($this->RequesterID) > 0) {
            $scoping = $this->document->createElementNS(Constants::NS_SAMLP, 'Scoping');
            $root->appendChild($scoping);
            if ($this->ProxyCount !== null) {
                $scoping->setAttribute('ProxyCount', $this->ProxyCount);
            }
            if (count($this->IDPList) > 0) {
                $idplist = $this->document->createElementNS(Constants::NS_SAMLP, 'IDPList');
                foreach ($this->IDPList as $provider) {
                    $idpEntry = $this->document->createElementNS(Constants::NS_SAMLP, 'IDPEntry');
                    if (is_string($provider)) {
                        $idpEntry->setAttribute('ProviderID', $provider);
                    } elseif (is_array($provider)) {
                        foreach ($provider as $attribute => $value) {
                            if (in_array($attribute, [
                                'ProviderID',
                                'Loc',
                                'Name'
                            ], true)) {
                                $idpEntry->setAttribute($attribute, $value);
                            }
                        }
                    }
                    $idplist->appendChild($idpEntry);
                }
                $scoping->appendChild($idplist);
            }
            if (count($this->RequesterID) > 0) {
                Utils::addStrings($scoping, Constants::NS_SAMLP, 'RequesterID', false, $this->RequesterID);
            }
        }

        return $root;
    }


    /**
     * Add a Subject-node to the assertion.
     *
     * @param \DOMElement $root The assertion element we should add the subject to.
     * @return void
     */
    private function addSubject(\DOMElement $root)
    {
        // If there is no nameId (encrypted or not) there is nothing to create a subject for
        if ($this->nameId === null && $this->encryptedNameId === null) {
            return;
        }

        $subject = $root->ownerDocument->createElementNS(Constants::NS_SAML, 'saml:Subject');
        $root->appendChild($subject);

        if ($this->encryptedNameId === null) {
            $this->nameId->toXML($subject);
        } else {
            $eid = $subject->ownerDocument->createElementNS(Constants::NS_SAML, 'saml:EncryptedID');
            $eid->appendChild($subject->ownerDocument->importNode($this->encryptedNameId, true));
            $subject->appendChild($eid);
        }

        foreach ($this->subjectConfirmation as $sc) {
            $sc->toXML($subject);
        }
    }


    /**
     * Add a Conditions-node to the request.
     *
     * @param \DOMElement $root The request element we should add the conditions to.
     * @return void
     */
    private function addConditions(\DOMElement $root)
    {
        if ($this->audiences !== []) {
            $document = $root->ownerDocument;

            $conditions = $document->createElementNS(Constants::NS_SAML, 'saml:Conditions');
            $root->appendChild($conditions);

            $ar = $document->createElementNS(Constants::NS_SAML, 'saml:AudienceRestriction');
            $conditions->appendChild($ar);

            Utils::addStrings($ar, Constants::NS_SAML, 'saml:Audience', false, $this->getAudiences());
        }
    }
}
