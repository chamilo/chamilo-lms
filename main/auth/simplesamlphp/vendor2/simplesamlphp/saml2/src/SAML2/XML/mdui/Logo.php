<?php

namespace SAML2\XML\mdui;

use Webmozart\Assert\Assert;

/**
 * Class for handling the Logo metadata extensions for login and discovery user interface
 *
 * @link: http://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-metadata-ui/v1.0/sstc-saml-metadata-ui-v1.0.pdf
 * @package SimpleSAMLphp
 */
class Logo
{
    /**
     * The url of this logo.
     *
     * @var string
     */
    public $url;

    /**
     * The width of this logo.
     *
     * @var int
     */
    public $width;

    /**
     * The height of this logo.
     *
     * @var int
     */
    public $height;

    /**
     * The language of this item.
     *
     * @var string
     */
    public $lang;


    /**
     * Initialize a Logo.
     *
     * @param \DOMElement|null $xml The XML element we should load.
     * @throws \Exception
     */
    public function __construct(\DOMElement $xml = null)
    {
        if ($xml === null) {
            return;
        }

        if (!$xml->hasAttribute('width')) {
            throw new \Exception('Missing width of Logo.');
        }
        if (!$xml->hasAttribute('height')) {
            throw new \Exception('Missing height of Logo.');
        }
        if (!is_string($xml->textContent) || !strlen($xml->textContent)) {
            throw new \Exception('Missing url value for Logo.');
        }
        $this->setUrl($xml->textContent);
        $this->setWidth(intval($xml->getAttribute('width')));
        $this->setHeight(intval($xml->getAttribute('height')));
        if ($xml->hasAttribute('xml:lang')) {
            $this->setLanguage($xml->getAttribute('xml:lang'));
        }
    }


    /**
     * Collect the value of the url-property
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }


    /**
     * Set the value of the url-property
     * @param string $url
     */
    public function setUrl($url)
    {
        Assert::string($url);
        if (!filter_var(trim($url), FILTER_VALIDATE_URL) && substr(trim($url), 0, 5) !== 'data:') {
            throw new \InvalidArgumentException('mdui:Logo is not a valid URL.');
        }
        $this->url = $url;
    }


    /**
     * Collect the value of the lang-property
     * @return string
     */
    public function getLanguage()
    {
        return $this->lang;
    }


    /**
     * Set the value of the lang-property
     * @param string $lang
     * @return void
     */
    public function setLanguage($lang)
    {
        Assert::string($lang);
        $this->lang = $lang;
    }


    /**
     * Collect the value of the height-property
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }


    /**
     * Set the value of the height-property
     * @param int $height
     * @return void
     */
    public function setHeight($height)
    {
        Assert::integer($height);
        $this->height = $height;
    }


    /**
     * Collect the value of the width-property
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }


    /**
     * Set the value of the width-property
     * @param int $width
     * @return void
     */
    public function setWidth($width)
    {
        Assert::integer($width);
        $this->width = $width;
    }


    /**
     * Convert this Logo to XML.
     *
     * @param \DOMElement $parent The element we should append this Logo to.
     * @return \DOMElement
     */
    public function toXML(\DOMElement $parent)
    {
        Assert::integer($this->getWidth());
        Assert::integer($this->getHeight());
        Assert::string($this->getUrl());

        $doc = $parent->ownerDocument;

        $e = $doc->createElementNS(Common::NS, 'mdui:Logo');
        $e->appendChild($doc->createTextNode($this->getUrl()));
        $e->setAttribute('width', intval($this->getWidth()));
        $e->setAttribute('height', intval($this->getHeight()));
        if ($this->getLanguage() !== null) {
            $e->setAttribute('xml:lang', $this->getLanguage());
        }
        $parent->appendChild($e);

        return $e;
    }
}
