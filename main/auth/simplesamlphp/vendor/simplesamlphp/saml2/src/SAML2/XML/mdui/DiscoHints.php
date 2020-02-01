<?php

namespace SAML2\XML\mdui;

use SAML2\Utils;
use SAML2\XML\Chunk;
use Webmozart\Assert\Assert;

/**
 * Class for handling the metadata extensions for login and discovery user interface
 *
 * @link: http://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-metadata-ui/v1.0/sstc-saml-metadata-ui-v1.0.pdf
 * @package SimpleSAMLphp
 */
class DiscoHints
{
    /**
     * Array with child elements.
     *
     * The elements can be any of the other \SAML2\XML\mdui\* elements.
     *
     * @var \SAML2\XML\Chunk[]
     */
    public $children = [];

    /**
     * The IPHint, as an array of strings.
     *
     * @var string[]
     */
    public $IPHint = [];

    /**
     * The DomainHint, as an array of strings.
     *
     * @var string[]
     */
    public $DomainHint = [];

    /**
     * The GeolocationHint, as an array of strings.
     *
     * @var string[]
     */
    public $GeolocationHint = [];


    /**
     * Create a DiscoHints element.
     *
     * @param \DOMElement|null $xml The XML element we should load.
     */
    public function __construct(\DOMElement $xml = null)
    {
        if ($xml === null) {
            return;
        }

        $this->setIPHint(Utils::extractStrings($xml, Common::NS, 'IPHint'));
        $this->setDomainHint(Utils::extractStrings($xml, Common::NS, 'DomainHint'));
        $this->setGeolocationHint(Utils::extractStrings($xml, Common::NS, 'GeolocationHint'));

        foreach (Utils::xpQuery($xml, "./*[namespace-uri()!='".Common::NS."']") as $node) {
            $this->addChildren(new Chunk($node));
        }
    }


    /**
     * Collect the value of the IPHint-property
     * @return string[]
     */
    public function getIPHint()
    {
        return $this->IPHint;
    }


    /**
     * Set the value of the IPHint-property
     * @param string[] $hints
     * @return void
     */
    public function setIPHint(array $hints)
    {
        $this->IPHint = $hints;
    }


    /**
     * Collect the value of the DomainHint-property
     * @return string[]
     */
    public function getDomainHint()
    {
        return $this->DomainHint;
    }


    /**
     * Set the value of the DomainHint-property
     * @param string[] $hints
     * @return void
     */
    public function setDomainHint(array $hints)
    {
        $this->DomainHint = $hints;
    }


    /**
     * Collect the value of the GeolocationHint-property
     * @return string[]
     */
    public function getGeolocationHint()
    {
        return $this->GeolocationHint;
    }


    /**
     * Set the value of the GeolocationHint-property
     * @param string[] $hints
     * @return void
     */
    public function setGeolocationHint(array $hints)
    {
        $this->GeolocationHint = $hints;
    }


    /**
     * Collect the value of the children-property
     * @return \SAML2\XML\Chunk[]
     */
    public function getChildren()
    {
        return $this->children;
    }


    /**
     * Set the value of the childen-property
     * @param array $children
     * @return void
     */
    public function setChildren(array $children)
    {
        $this->children = $children;
    }


    /**
     * Add the value to the children-property
     * @param \SAML2\XML\Chunk $child
     * @return void
     */
    public function addChildren(Chunk $child)
    {
        Assert::isInstanceOf($child, Chunk::class);
        $this->children[] = $child;
    }


    /**
     * Convert this DiscoHints to XML.
     *
     * @param \DOMElement $parent The element we should append to.
     * @return \DOMElement|null
     */
    public function toXML(\DOMElement $parent)
    {
        Assert::isArray($IPHint = $this->getIPHint());
        Assert::isArray($DomainHint = $this->getDomainHint());
        Assert::isArray($GeolocationHint = $this->getGeolocationHint());
        Assert::isArray($children = $this->getChildren());

        if (!empty($IPHint)
         || !empty($DomainHint)
         || !empty($GeolocationHint)
         || !empty($children)) {
            $doc = $parent->ownerDocument;

            $e = $doc->createElementNS(Common::NS, 'mdui:DiscoHints');
            $parent->appendChild($e);

            if (!empty($children)) {
                foreach ($this->getChildren() as $child) {
                    $child->toXML($e);
                }
            }

            Utils::addStrings($e, Common::NS, 'mdui:IPHint', false, $this->getIPHint());
            Utils::addStrings($e, Common::NS, 'mdui:DomainHint', false, $this->getDomainHint());
            Utils::addStrings($e, Common::NS, 'mdui:GeolocationHint', false, $this->getGeolocationHint());

            return $e;
        }

        return null;
    }
}
