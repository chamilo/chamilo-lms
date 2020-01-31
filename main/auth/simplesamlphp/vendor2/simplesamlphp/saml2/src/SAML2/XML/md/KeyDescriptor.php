<?php

namespace SAML2\XML\md;

use SAML2\Constants;
use SAML2\Utils;
use SAML2\XML\Chunk;
use SAML2\XML\ds\KeyInfo;
use Webmozart\Assert\Assert;

/**
 * Class representing a KeyDescriptor element.
 *
 * @package SimpleSAMLphp
 */
class KeyDescriptor
{
    /**
     * What this key can be used for.
     *
     * 'encryption', 'signing' or null.
     *
     * @var string|null
     */
    public $use;

    /**
     * The KeyInfo for this key.
     *
     * @var \SAML2\XML\ds\KeyInfo
     */
    public $KeyInfo;

    /**
     * Supported EncryptionMethods.
     *
     * Array of \SAML2\XML\Chunk objects.
     *
     * @var \SAML2\XML\Chunk[]
     */
    public $EncryptionMethod = [];


    /**
     * Initialize an KeyDescriptor.
     *
     * @param \DOMElement|null $xml The XML element we should load.
     * @throws \Exception
     */
    public function __construct(\DOMElement $xml = null)
    {
        if ($xml === null) {
            return;
        }

        if ($xml->hasAttribute('use')) {
            $this->setUse($xml->getAttribute('use'));
        }

        $keyInfo = Utils::xpQuery($xml, './ds:KeyInfo');
        if (count($keyInfo) > 1) {
            throw new \Exception('More than one ds:KeyInfo in the KeyDescriptor.');
        } elseif (empty($keyInfo)) {
            throw new \Exception('No ds:KeyInfo in the KeyDescriptor.');
        }
        $this->setKeyInfo(new KeyInfo($keyInfo[0]));

        foreach (Utils::xpQuery($xml, './saml_metadata:EncryptionMethod') as $em) {
            $this->addEncryptionMethod(new Chunk($em));
        }
    }


    /**
     * Collect the value of the use-property
     * @return string
     */
    public function getUse()
    {
        return $this->use;
    }


    /**
     * Set the value of the use-property
     * @param string|null $use
     * @return void
     */
    public function setUse($use)
    {
        Assert::nullOrString($use);
        $this->use = $use;
    }


    /**
     * Collect the value of the KeyInfo-property
     * @return \SAML2\XML\ds\KeyInfo
     */
    public function getKeyInfo()
    {
        return $this->KeyInfo;
    }


    /**
     * Set the value of the KeyInfo-property
     * @param \SAML2\XML\ds\KeyInfo $keyInfo
     * @return void
     */
    public function setKeyInfo(KeyInfo $keyInfo)
    {
        $this->KeyInfo = $keyInfo;
    }


    /**
     * Collect the value of the EncryptionMethod-property
     * @return \SAML2\XML\Chunk[]
     */
    public function getEncryptionMethod()
    {
        return $this->EncryptionMethod;
    }


    /**
     * Set the value of the EncryptionMethod-property
     * @param \SAML2\XML\Chunk[] $encryptionMethod
     * @return void
     */
    public function setEncryptionMethod(array $encryptionMethod)
    {
        $this->EncryptionMethod = $encryptionMethod;
    }


    /**
     * Add the value to the EncryptionMethod-property
     * @param \SAML2\XML\Chunk $encryptionMethod
     * @return void
     */
    public function addEncryptionMethod(Chunk $encryptionMethod)
    {
        $this->EncryptionMethod[] = $encryptionMethod;
    }


    /**
     * Convert this KeyDescriptor to XML.
     *
     * @param \DOMElement $parent The element we should append this KeyDescriptor to.
     * @return \DOMElement
     */
    public function toXML(\DOMElement $parent)
    {
        Assert::nullOrString($this->getUse());
        Assert::isInstanceOf($this->getKeyInfo(), KeyInfo::class);
        Assert::isArray($this->getEncryptionMethod());

        $doc = $parent->ownerDocument;

        $e = $doc->createElementNS(Constants::NS_MD, 'md:KeyDescriptor');
        $parent->appendChild($e);

        if ($this->getUse() !== null) {
            $e->setAttribute('use', $this->getUse());
        }

        $this->getKeyInfo()->toXML($e);

        foreach ($this->getEncryptionMethod() as $em) {
            $em->toXML($e);
        }

        return $e;
    }
}
