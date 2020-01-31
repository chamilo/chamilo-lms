<?php

namespace SAML2\XML\md;

use SAML2\Constants;
use SAML2\Utils;
use SAML2\XML\alg\Common as ALG;
use SAML2\XML\Chunk;
use SAML2\XML\mdattr\EntityAttributes;
use SAML2\XML\mdrpi\Common as MDRPI;
use SAML2\XML\mdui\Common as MDUI;
use SAML2\XML\shibmd\Scope;

/**
 * Class for handling SAML2 metadata extensions.
 *
 * @package SimpleSAMLphp
 */
class Extensions
{
    /**
     * Get a list of Extensions in the given element.
     *
     * @param  \DOMElement $parent The element that may contain the md:Extensions element.
     * @return \SAML2\XML\Chunk[]  Array of extensions.
     */
    public static function getList(\DOMElement $parent)
    {
        $ret = [];
        $supported = [
            Scope::NS => [
                'Scope' => '\SAML2\XML\shibmd\Scope',
            ],
            EntityAttributes::NS => [
                'EntityAttributes' => '\SAML2\XML\mdattr\EntityAttributes',
            ],
            MDRPI::NS_MDRPI => [
                'RegistrationInfo' => '\SAML2\XML\mdrpi\RegistrationInfo',
                'PublicationInfo' => '\SAML2\XML\mdrpi\PublicationInfo',
            ],
            MDUI::NS => [
                'UIInfo' => '\SAML2\XML\mdui\UIInfo',
                'DiscoHints' => '\SAML2\XML\mdui\DiscoHints',
            ],
            ALG::NS => [
                'DigestMethod' => '\SAML2\XML\alg\DigestMethod',
                'SigningMethod' => '\SAML2\XML\alg\SigningMethod',
            ],
        ];

        foreach (Utils::xpQuery($parent, './saml_metadata:Extensions/*') as $node) {
            if (array_key_exists($node->namespaceURI, $supported) &&
                array_key_exists($node->localName, $supported[$node->namespaceURI])
            ) {
                $ret[] = new $supported[$node->namespaceURI][$node->localName]($node);
            } else {
                $ret[] = new Chunk($node);
            }
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

        $extElement = $parent->ownerDocument->createElementNS(Constants::NS_MD, 'md:Extensions');
        $parent->appendChild($extElement);

        foreach ($extensions as $ext) {
            $ext->toXML($extElement);
        }
    }
}
