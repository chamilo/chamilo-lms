<?php

namespace SAML2\XML\mdattr;

use SAML2\Utils;
use SAML2\XML\Chunk;
use SAML2\XML\saml\Attribute;
use Webmozart\Assert\Assert;

/**
 * Class for handling the EntityAttributes metadata extension.
 *
 * @link: http://docs.oasis-open.org/security/saml/Post2.0/sstc-metadata-attr-cs-01.pdf
 * @package SimpleSAMLphp
 */
class EntityAttributes
{
    /**
     * The namespace used for the EntityAttributes extension.
     */
    const NS = 'urn:oasis:names:tc:SAML:metadata:attribute';

    /**
     * Array with child elements.
     *
     * The elements can be \SAML2\XML\saml\Attribute or \SAML2\XML\Chunk elements.
     *
     * @var (\SAML2\XML\saml\Attribute|\SAML2\XML\Chunk)[]
     */
    public $children;


    /**
     * Create a EntityAttributes element.
     *
     * @param \DOMElement|null $xml The XML element we should load.
     */
    public function __construct(\DOMElement $xml = null)
    {
        if ($xml === null) {
            return;
        }

        foreach (Utils::xpQuery($xml, './saml_assertion:Attribute|./saml_assertion:Assertion') as $node) {
            if ($node->localName === 'Attribute') {
                $this->addChildren(new Attribute($node));
            } else {
                $this->addChildren(new Chunk($node));
            }
        }
    }


    /**
     * Collect the value of the children-property
     * @return (\SAML2\XML\Chunk|\SAML2\XML\saml\Attribute)[]
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
     * @param \SAML2\XML\Chunk|\SAML2\XML\saml\Attribute $child
     * @return void
     */
    public function addChildren($child)
    {
        Assert::isInstanceOfAny($child, [Chunk::class, Attribute::class]);
        $this->children[] = $child;
    }


    /**
     * Convert this EntityAttributes to XML.
     *
     * @param \DOMElement $parent The element we should append to.
     * @return \DOMElement
     */
    public function toXML(\DOMElement $parent)
    {
        Assert::isArray($this->getChildren());

        $doc = $parent->ownerDocument;

        $e = $doc->createElementNS(EntityAttributes::NS, 'mdattr:EntityAttributes');
        $parent->appendChild($e);

        /** @var \SAML2\XML\saml\Attribute|\SAML2\XML\Chunk $child */
        foreach ($this->getChildren() as $child) {
            $child->toXML($e);
        }

        return $e;
    }
}
