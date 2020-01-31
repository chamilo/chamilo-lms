<?php

namespace SAML2\XML\samlp;

use SAML2\Constants;
use SAML2\Utils;
use SAML2\XML\Chunk;

/**
 * Class for handling SAML2 extensions.
 *
 * @package SimpleSAMLphp
 */
class Extensions
{
    /**
     * Get a list of Extensions in the given element.
     *
     * @param  \DOMElement $parent The element that may contain the samlp:Extensions element.
     * @return array      Array of extensions.
     */
    public static function getList(\DOMElement $parent)
    {
        $ret = [];
        foreach (Utils::xpQuery($parent, './saml_protocol:Extensions/*') as $node) {
            $ret[] = new Chunk($node);
        }

        return $ret;
    }


    /**
     * Add a list of Extensions to the given element.
     *
     * @param \DOMElement        $parent     The element we should add the extensions to.
     * @param \SAML2\XML\Chunk[] $extensions List of extension objects.
     * @return void
     */
    public static function addList(\DOMElement $parent, array $extensions)
    {
        if (empty($extensions)) {
            return;
        }

        $extElement = $parent->ownerDocument->createElementNS(Constants::NS_SAMLP, 'samlp:Extensions');
        $parent->appendChild($extElement);

        foreach ($extensions as $ext) {
            $ext->toXML($extElement);
        }
    }
}
