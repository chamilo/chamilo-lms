<?php

namespace SAML2\XML\ds;

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use SAML2\XML\Chunk;
use Webmozart\Assert\Assert;

/**
 * Class representing a ds:KeyInfo element.
 *
 * @package SimpleSAMLphp
 */
class KeyInfo
{
    /**
     * The Id attribute on this element.
     *
     * @var string|null
     */
    public $Id = null;

    /**
     * The various key information elements.
     *
     * Array with various elements describing this key.
     * Unknown elements will be represented by \SAML2\XML\Chunk.
     *
     * @var (\SAML2\XML\Chunk|\SAML2\XML\ds\KeyName|\SAML2\XML\ds\X509Data)[]
     */
    public $info = [];


    /**
     * Initialize a KeyInfo element.
     *
     * @param \DOMElement|null $xml The XML element we should load.
     */
    public function __construct(\DOMElement $xml = null)
    {
        if ($xml === null) {
            return;
        }

        if ($xml->hasAttribute('Id')) {
            $this->setId($xml->getAttribute('Id'));
        }

        for ($n = $xml->firstChild; $n !== null; $n = $n->nextSibling) {
            if (!($n instanceof \DOMElement)) {
                continue;
            }

            if ($n->namespaceURI !== XMLSecurityDSig::XMLDSIGNS) {
                $this->addInfo(new Chunk($n));
                continue;
            }
            switch ($n->localName) {
                case 'KeyName':
                    $this->addInfo(new KeyName($n));
                    break;
                case 'X509Data':
                    $this->addInfo(new X509Data($n));
                    break;
                default:
                    $this->addInfo(new Chunk($n));
                    break;
            }
        }
    }


    /**
     * Collect the value of the Id-property
     * @return string|null
     */
    public function getId()
    {
        return $this->Id;
    }


    /**
     * Set the value of the Id-property
     * @param string|null $id
     * @return void
     */
    public function setId($id = null)
    {
        Assert::nullOrString($id);
        $this->Id = $id;
    }


    /**
     * Collect the value of the info-property
     * @return array
     */
    public function getInfo()
    {
        return $this->info;
    }


    /**
     * Set the value of the info-property
     * @param array $info
     * @return void
     */
    public function setInfo(array $info)
    {
        $this->info = $info;
    }


    /**
     * Add the value to the info-property
     * @param \SAML2\XML\Chunk|\SAML2\XML\ds\KeyName|\SAML2\XML\ds\X509Data $info
     * @return void
     */
    public function addInfo($info)
    {
        Assert::isInstanceOfAny($info, [Chunk::class, KeyName::class, X509Data::class]);
        $this->info[] = $info;
    }


    /**
     * Convert this KeyInfo to XML.
     *
     * @param \DOMElement $parent The element we should append this KeyInfo to.
     * @return \DOMElement
     */
    public function toXML(\DOMElement $parent)
    {
        Assert::nullOrString($this->getId());
        Assert::isArray($this->getInfo());

        $doc = $parent->ownerDocument;

        $e = $doc->createElementNS(XMLSecurityDSig::XMLDSIGNS, 'ds:KeyInfo');
        $parent->appendChild($e);

        if ($this->getId() !== null) {
            $e->setAttribute('Id', $this->getId());
        }

        /** @var \SAML2\XML\Chunk|\SAML2\XML\ds\KeyName|\SAML2\XML\ds\X509Data $n */
        foreach ($this->getInfo() as $n) {
            $n->toXML($e);
        }

        return $e;
    }
}
