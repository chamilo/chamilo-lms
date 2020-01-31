<?php

namespace SAML2\XML\mdui;

use DOMElement;
use SAML2\Utils;
use SAML2\XML\Chunk;
use Webmozart\Assert\Assert;

/**
 * Class for handling the metadata extensions for login and discovery user interface
 *
 * @link: http://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-metadata-ui/v1.0/sstc-saml-metadata-ui-v1.0.pdf
 * @package SimpleSAMLphp
 */
class UIInfo
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
     * The DisplayName, as an array of language => translation.
     *
     * @var array
     */
    public $DisplayName = [];

    /**
     * The Description, as an array of language => translation.
     *
     * @var array
     */
    public $Description = [];

    /**
     * The InformationURL, as an array of language => url.
     *
     * @var array
     */
    public $InformationURL = [];

    /**
     * The PrivacyStatementURL, as an array of language => url.
     *
     * @var array
     */
    public $PrivacyStatementURL = [];

    /**
     * The Keywords, as an array of Keywords objects
     *
     * @var \SAML2\XML\mdui\Keywords[]
     */
    public $Keywords = [];

    /**
     * The Logo, as an array of Logo objects
     *
     * @var \SAML2\XML\mdui\Logo[]
     */
    public $Logo = [];


    /**
     * Create a UIInfo element.
     *
     * @param \DOMElement|null $xml The XML element we should load.
     */
    public function __construct(\DOMElement $xml = null)
    {
        if ($xml === null) {
            return;
        }

        $this->setDisplayName(Utils::extractLocalizedStrings($xml, Common::NS, 'DisplayName'));
        $this->setDescription(Utils::extractLocalizedStrings($xml, Common::NS, 'Description'));
        $this->setInformationURL(Utils::extractLocalizedStrings($xml, Common::NS, 'InformationURL'));
        $this->setPrivacyStatementURL(Utils::extractLocalizedStrings($xml, Common::NS, 'PrivacyStatementURL'));

        foreach (Utils::xpQuery($xml, './*') as $node) {
            if ($node->namespaceURI === Common::NS) {
                switch ($node->localName) {
                    case 'Keywords':
                        $this->addKeyword(new Keywords($node));
                        break;
                    case 'Logo':
                        $this->addLogo(new Logo($node));
                        break;
                }
            } else {
                $this->addChildren(new Chunk($node));
            }
        }
    }


    /**
     * Collect the value of the Keywords-property
     * @return \SAML2\XML\mdui\Keywords[]
     */
    public function getKeywords()
    {
        return $this->Keywords;
    }


    /**
     * Set the value of the Keywords-property
     * @param \SAML2\XML\mdui\Keywords[] $keywords
     * @return void
     */
    public function setKeywords(array $keywords)
    {
        Assert::allIsInstanceOf($keywords, Keywords::class);
        $this->Keywords = $keywords;
    }


    /**
     * Add the value to the Keywords-property
     * @param \SAML2\XML\mdui\Keywords $keyword
     * @return void
     */
    public function addKeyword(Keywords $keyword)
    {
        $this->Keywords[] = $keyword;
    }


    /**
     * Collect the value of the DisplayName-property
     * @return string[]
     */
    public function getDisplayName()
    {
        return $this->DisplayName;
    }


    /**
     * Set the value of the DisplayName-property
     * @param array $displayName
     * @return void
     */
    public function setDisplayName(array $displayName)
    {
        $this->DisplayName = $displayName;
    }


    /**
     * Collect the value of the Description-property
     * @return string[]
     */
    public function getDescription()
    {
        return $this->Description;
    }


    /**
     * Set the value of the Description-property
     * @param array $description
     * @return void
     */
    public function setDescription(array $description)
    {
        $this->Description = $description;
    }


    /**
     * Collect the value of the InformationURL-property
     * @return string[]
     */
    public function getInformationURL()
    {
        return $this->InformationURL;
    }


    /**
     * Set the value of the InformationURL-property
     * @param array $informationURL
     * @return void
     */
    public function setInformationURL(array $informationURL)
    {
        $this->InformationURL = $informationURL;
    }


    /**
     * Collect the value of the PrivacyStatementURL-property
     * @return string[]
     */
    public function getPrivacyStatementURL()
    {
        return $this->PrivacyStatementURL;
    }


    /**
     * Set the value of the PrivacyStatementURL-property
     * @param array $privacyStatementURL
     * @return void
     */
    public function setPrivacyStatementURL(array $privacyStatementURL)
    {
        $this->PrivacyStatementURL = $privacyStatementURL;
    }


    /**
     * Collect the value of the Logo-property
     * @return \SAML2\XML\mdui\Logo[]
     */
    public function getLogo()
    {
        return $this->Logo;
    }


    /**
     * Set the value of the Logo-property
     * @param \SAML2\XML\mdui\Logo $logo
     * @return void
     */
    public function setLogo(array $logo)
    {
        $this->Logo = $logo;
    }


    /**
     * Add the value to the Logo-property
     * @param \SAML2\XML\mdui\Logo $logo
     * @return void
     */
    public function addLogo(Logo $logo)
    {
        $this->Logo[] = $logo;
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
        $this->children[] = $child;
    }


    /**
     * Convert this UIInfo to XML.
     *
     * @param \DOMElement $parent The element we should append to.
     * @return \DOMElement|null
     */
    public function toXML(\DOMElement $parent)
    {
        Assert::isArray($displayName = $this->getDisplayName());
        Assert::isArray($description = $this->getDescription());
        Assert::isArray($informationURL = $this->getInformationURL());
        Assert::isArray($privacyStatementURL = $this->getPrivacyStatementURL());
        Assert::isArray($keywords = $this->getKeywords());
        Assert::isArray($logo = $this->getLogo());
        Assert::isArray($children = $this->getChildren());

        $e = null;
        if (!empty($displayName)
         || !empty($description)
         || !empty($informationURL)
         || !empty($privacyStatementURL)
         || !empty($keywords)
         || !empty($logo)
         || !empty($children)) {
            $doc = $parent->ownerDocument;

            $e = $doc->createElementNS(Common::NS, 'mdui:UIInfo');
            $parent->appendChild($e);

            Utils::addStrings($e, Common::NS, 'mdui:DisplayName', true, $this->getDisplayName());
            Utils::addStrings($e, Common::NS, 'mdui:Description', true, $this->getDescription());
            Utils::addStrings($e, Common::NS, 'mdui:InformationURL', true, $this->getInformationURL());
            Utils::addStrings($e, Common::NS, 'mdui:PrivacyStatementURL', true, $this->getPrivacyStatementURL());

            if ($this->getKeywords() !== null) {
                foreach ($this->getKeywords() as $child) {
                    $child->toXML($e);
                }
            }

            if ($this->getLogo() !== null) {
                foreach ($this->getLogo() as $child) {
                    $child->toXML($e);
                }
            }

            if ($this->getChildren() !== null) {
                foreach ($this->getChildren() as $child) {
                    $child->toXML($e);
                }
            }
        }

        return $e;
    }
}
