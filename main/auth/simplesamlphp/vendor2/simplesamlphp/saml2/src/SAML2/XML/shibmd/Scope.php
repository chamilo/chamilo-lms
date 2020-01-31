<?php

namespace SAML2\XML\shibmd;

use SAML2\Utils;
use Webmozart\Assert\Assert;

/**
 * Class which represents the Scope element found in Shibboleth metadata.
 *
 * @link https://wiki.shibboleth.net/confluence/display/SHIB/ShibbolethMetadataProfile
 * @package SimpleSAMLphp
 */
class Scope
{
    /**
     * The namespace used for the Scope extension element.
     */
    const NS = 'urn:mace:shibboleth:metadata:1.0';

    /**
     * The scope.
     *
     * @var string
     */
    public $scope;

    /**
     * Whether this is a regexp scope.
     *
     * @var bool
     */
    public $regexp = false;


    /**
     * Create a Scope.
     *
     * @param \DOMElement|null $xml The XML element we should load.
     */
    public function __construct(\DOMElement $xml = null)
    {
        if ($xml === null) {
            return;
        }

        $this->setScope($xml->textContent);
        $this->setIsRegexpScope(Utils::parseBoolean($xml, 'regexp', false));
    }


    /**
     * Collect the value of the scope-property
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }


    /**
     * Set the value of the scope-property
     * @param string $scope
     * @return void
     */
    public function setScope($scope)
    {
        Assert::string($scope);
        $this->scope = $scope;
    }


    /**
     * Collect the value of the regexp-property
     * @return boolean
     */
    public function isRegexpScope()
    {
        return $this->regexp;
    }


    /**
     * Set the value of the regexp-property
     * @param boolean $regexp
     * @return void
     */
    public function setIsRegexpScope($regexp)
    {
        Assert::boolean($regexp);
        $this->regexp = $regexp;
    }


    /**
     * Convert this Scope to XML.
     *
     * @param \DOMElement $parent The element we should append this Scope to.
     * @return \DOMElement
     */
    public function toXML(\DOMElement $parent)
    {
        Assert::string($this->getScope());
        Assert::nullOrBoolean($this->isRegexpScope());

        $doc = $parent->ownerDocument;

        $e = $doc->createElementNS(Scope::NS, 'shibmd:Scope');
        $parent->appendChild($e);

        $e->appendChild($doc->createTextNode($this->getScope()));

        if ($this->isRegexpScope() === true) {
            $e->setAttribute('regexp', 'true');
        } else {
            $e->setAttribute('regexp', 'false');
        }

        return $e;
    }
}
