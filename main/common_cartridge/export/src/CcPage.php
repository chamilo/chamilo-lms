<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_page.php under GNU/GPL license */

class CcPage extends CcGeneralFile
{
    protected $rootns = 'xmlns';
    protected $rootname = 'html';
    protected $ccnamespaces = ['xmlns' => 'http://www.w3.org/1999/xhtml'];

    protected $content = null;
    protected $title = null;
    protected $intro = null;

    public function setContent($value)
    {
        // We are not cleaning up this one on purpose.
        $this->content = $value;
    }

    public function setTitle($value)
    {
        $this->title = self::safexml($value);
    }

    public function setIntro($value)
    {
        $this->intro = self::safexml(strip_tags($value));
    }

    public function onSave()
    {
        $rns = $this->ccnamespaces[$this->rootns];
        // Add the basic tags.
        $head = $this->appendNewElementNs($this->root, $rns, 'head');
        $this->appendNewAttributeNs($head, $rns, 'profile', 'http://dublincore.org/documents/dc-html/');

        // Linking Dublin Core Metadata 1.1.
        $link_dc = $this->appendNewElementNs($head, $rns, 'link');
        $this->appendNewAttributeNs($link_dc, $rns, 'rel', 'schema.DC');
        $this->appendNewAttributeNs($link_dc, $rns, 'href', 'http://purl.org/dc/elements/1.1/');
        $link_dcterms = $this->appendNewElementNs($head, $rns, 'link');
        $this->appendNewAttributeNs($link_dcterms, $rns, 'rel', 'schema.DCTERMS');
        $this->appendNewAttributeNs($link_dcterms, $rns, 'href', 'http://purl.org/dc/terms/');
        // Content type.
        $meta_type = $this->appendNewElementNs($head, $rns, 'meta');
        $this->appendNewAttributeNs($meta_type, $rns, 'name', 'DC.type');
        $this->appendNewAttributeNs($meta_type, $rns, 'scheme', 'DCTERMS.DCMIType');
        $this->appendNewAttributeNs($meta_type, $rns, 'content', 'Text');

        // Content description.
        if (!empty($this->intro)) {
            $meta_description = $this->appendNewElementNs($head, $rns, 'meta');
            $this->appendNewAttributeNs($meta_description, $rns, 'name', 'DC.description');
            $this->appendNewAttributeNs($meta_description, $rns, 'content', $this->intro);
        }

        $meta = $this->appendNewElementNs($head, $rns, 'meta');
        $this->appendNewAttributeNs($meta, $rns, 'http-equiv', 'Content-type');
        $this->appendNewAttributeNs($meta, $rns, 'content', 'text/html; charset=UTF-8');
        // Set the title.
        $title = $this->appendNewElementNs($head, $rns, 'title', $this->title);
        $body = $this->appendNewElementNs($this->root, $rns, 'body');
        // We are unable to use DOM for embedding HTML due to numerous content errors.
        // Therefore we place a dummy tag that will be later replaced with the real content.
        $this->appendNewElementNs($body, $rns, 'div', '##REPLACE##');

        return true;
    }

    public function saveTo($fname)
    {
        $result = $this->onSave();
        if ($result) {
            $dret = str_replace('<?xml version="1.0"?>'."\n", '', $this->viewXML());
            $dret = str_replace('<div>##REPLACE##</div>', $this->content, $dret);
            $result = (file_put_contents($fname, $dret) !== false);
            if ($result) {
                $this->filename = $fname;
                $this->processPath();
            }
        }

        return $result;
    }

    protected function onCreate()
    {
        $impl = new DOMImplementation();
        $dtd = $impl->createDocumentType('html',
                                           '-//W3C//DTD XHTML 1.0 Strict//EN',
                                           'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd');
        $doc = $impl->createDocument($this->ccnamespaces[$this->rootns], null, $dtd);
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = true;
        $this->doc = $doc;
        parent::onCreate();
    }
}
