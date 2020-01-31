<?php

namespace SAML2;

use RobRichards\XMLSecLibs\XMLSecEnc;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\XML\saml\NameID;
use Webmozart\Assert\Assert;

/**
 * Class for SAML 2 logout request messages.
 *
 * @package SimpleSAMLphp
 */
class LogoutRequest extends Request
{
    /**
     * The expiration time of this request.
     *
     * @var int|null
     */
    private $notOnOrAfter;

    /**
     * The encrypted NameID in the request.
     *
     * If this is not null, the NameID needs decryption before it can be accessed.
     *
     * @var \DOMElement|null
     */
    private $encryptedNameId;

    /**
     * The name identifier of the session that should be terminated.
     *
     * @var \SAML2\XML\saml\NameID
     */
    private $nameId;

    /**
     * The SessionIndexes of the sessions that should be terminated.
     *
     * @var array
     */
    private $sessionIndexes;


    /**
     * Constructor for SAML 2 logout request messages.
     *
     * @param \DOMElement|null $xml The input message.
     * @throws \Exception
     */
    public function __construct(\DOMElement $xml = null)
    {
        parent::__construct('LogoutRequest', $xml);

        $this->sessionIndexes = [];

        if ($xml === null) {
            return;
        }

        if ($xml->hasAttribute('NotOnOrAfter')) {
            $this->setNotOnOrAfter(Utils::xsDateTimeToTimestamp($xml->getAttribute('NotOnOrAfter')));
        }

        $nameId = Utils::xpQuery($xml, './saml_assertion:NameID | ./saml_assertion:EncryptedID/xenc:EncryptedData');
        if (empty($nameId)) {
            throw new \Exception('Missing <saml:NameID> or <saml:EncryptedID> in <samlp:LogoutRequest>.');
        } elseif (count($nameId) > 1) {
            throw new \Exception('More than one <saml:NameID> or <saml:EncryptedD> in <samlp:LogoutRequest>.');
        }
        $nameId = $nameId[0];
        if ($nameId->localName === 'EncryptedData') {
            /* The NameID element is encrypted. */
            $this->setEncryptedNameId($nameId);
        } else {
            $this->setNameId(new NameID($nameId));
        }

        $sessionIndexes = Utils::xpQuery($xml, './saml_protocol:SessionIndex');
        foreach ($sessionIndexes as $sessionIndex) {
            $this->sessionIndexes[] = trim($sessionIndex->textContent);
        }
    }


    /**
     * Retrieve the expiration time of this request.
     *
     * @return int|null The expiration time of this request.
     */
    public function getNotOnOrAfter()
    {
        return $this->notOnOrAfter;
    }


    /**
     * Set the expiration time of this request.
     *
     * @param int|null $notOnOrAfter The expiration time of this request.
     * @return void
     */
    public function setNotOnOrAfter($notOnOrAfter)
    {
        Assert::nullOrInteger($notOnOrAfter);

        $this->notOnOrAfter = $notOnOrAfter;
    }


    /**
     * Check whether the NameId is encrypted.
     *
     * @return bool True if the NameId is encrypted, false if not.
     */
    public function isNameIdEncrypted()
    {
        if ($this->getEncryptedNameId() !== null) {
            return true;
        }

        return false;
    }


    /**
     * Encrypt the NameID in the LogoutRequest.
     *
     * @param XMLSecurityKey $key The encryption key.
     * @return void
     */
    public function encryptNameId(XMLSecurityKey $key)
    {
        /* First create a XML representation of the NameID. */
        $doc = DOMDocumentFactory::create();
        $root = $doc->createElement('root');
        $doc->appendChild($root);
        $this->getNameId()->toXML($root);
        $nameId = $root->firstChild;

        Utils::getContainer()->debugMessage($nameId, 'encrypt');

        /* Encrypt the NameID. */
        $enc = new XMLSecEnc();
        $enc->setNode($nameId);
        $enc->type = XMLSecEnc::Element;

        $symmetricKey = new XMLSecurityKey(XMLSecurityKey::AES128_CBC);
        $symmetricKey->generateSessionKey();
        $enc->encryptKey($key, $symmetricKey);

        $this->setEncryptedNameId($enc->encryptNode($symmetricKey));
        $this->setNameId(null);
    }


    /**
     * Decrypt the NameID in the LogoutRequest.
     *
     * @param XMLSecurityKey $key       The decryption key.
     * @param array          $blacklist Blacklisted decryption algorithms.
     * @return void
     */
    public function decryptNameId(XMLSecurityKey $key, array $blacklist = [])
    {
        if ($this->getEncryptedNameId() === null) {
            /* No NameID to decrypt. */
            return;
        }

        $nameId = Utils::decryptElement($this->getEncryptedNameId(), $key, $blacklist);
        Utils::getContainer()->debugMessage($nameId, 'decrypt');
        $this->setNameId(new NameID($nameId));

        $this->setEncryptedNameId(null);
    }


    /**
     * Retrieve the name identifier of the session that should be terminated.
     *
     * @throws \Exception
     * @return \SAML2\XML\saml\NameID The name identifier of the session that should be terminated.
     */
    public function getNameId()
    {
        if ($this->getEncryptedNameId() !== null) {
            throw new \Exception('Attempted to retrieve encrypted NameID without decrypting it first.');
        }

        return $this->nameId;
    }


    /**
     * Set the name identifier of the session that should be terminated.
     *
     * @param \SAML2\XML\saml\NameID|array|null $nameId The name identifier of the session that should be terminated.
     * @return void
     */
    public function setNameId($nameId)
    {
        Assert::true(is_array($nameId) || $nameId instanceof NameID || is_null($nameId));

        if (is_array($nameId)) {
            $nameId = NameID::fromArray($nameId);
        }
        $this->nameId = $nameId;
    }


    /**
     * Retrieve the encrypted name identifier.
     *
     * @return \DOMElement|null
     */
    private function getEncryptedNameId()
    {
        return $this->encryptedNameId;
    }


    /**
     * Set the encrypted name identifier.
     *
     * @param \DOMElement|null $nameId The name identifier of the session that should be terminated.
     * @return void
     */
    private function setEncryptedNameId(\DOMElement $nameId = null)
    {
        $this->encryptedNameId = $nameId;
    }


    /**
     * Retrieve the SessionIndexes of the sessions that should be terminated.
     *
     * @return array The SessionIndexes, or an empty array if all sessions should be terminated.
     */
    public function getSessionIndexes()
    {
        return $this->sessionIndexes;
    }


    /**
     * Set the SessionIndexes of the sessions that should be terminated.
     *
     * @param array $sessionIndexes The SessionIndexes, or an empty array if all sessions should be terminated.
     * @return void
     */
    public function setSessionIndexes(array $sessionIndexes)
    {
        $this->sessionIndexes = $sessionIndexes;
    }


    /**
     * Retrieve the sesion index of the session that should be terminated.
     *
     * @return string|null The sesion index of the session that should be terminated.
     */
    public function getSessionIndex()
    {
        if (empty($this->sessionIndexes)) {
            return null;
        }

        return $this->sessionIndexes[0];
    }


    /**
     * Set the sesion index of the session that should be terminated.
     *
     * @param string|null $sessionIndex The sesion index of the session that should be terminated.
     * @return void
     */
    public function setSessionIndex($sessionIndex)
    {
        Assert::nullOrString($sessionIndex);

        if (is_null($sessionIndex)) {
            $this->sessionIndexes = [];
        } else {
            $this->sessionIndexes = [$sessionIndex];
        }
    }


    /**
     * Convert this logout request message to an XML element.
     *
     * @return \DOMElement This logout request.
     */
    public function toUnsignedXML()
    {
        $root = parent::toUnsignedXML();

        if ($this->notOnOrAfter !== null) {
            $root->setAttribute('NotOnOrAfter', gmdate('Y-m-d\TH:i:s\Z', $this->getNotOnOrAfter()));
        }

        if ($this->getEncryptedNameId() === null) {
            $this->nameId->toXML($root);
        } else {
            $eid = $root->ownerDocument->createElementNS(Constants::NS_SAML, 'saml:'.'EncryptedID');
            $root->appendChild($eid);
            $eid->appendChild($root->ownerDocument->importNode($this->getEncryptedNameId(), true));
        }

        foreach ($this->sessionIndexes as $sessionIndex) {
            Utils::addString($root, Constants::NS_SAMLP, 'SessionIndex', $sessionIndex);
        }

        return $root;
    }
}
