<?php
/* For licensing terms, see /license.txt */

class CcPage extends CcGeneralFile {
    protected $rootns = 'xmlns';
    protected $rootname = 'html';
    protected $ccnamespaces = array('xmlns' => 'http://www.w3.org/1999/xhtml');

    protected $content = null;
    protected $title = null;
    protected $intro = null;

    public function set_content($value) {
        // We are not cleaning up this one on purpose.
        $this->content = $value;
    }

    public function set_title($value) {
        $this->title = self::safexml($value);
    }

    public function set_intro($value) {
        $this->intro = self::safexml(strip_tags($value));
    }

    protected function on_create() {
        $impl = new DOMImplementation();
        $dtd  = $impl->createDocumentType( 'html',
                                           '-//W3C//DTD XHTML 1.0 Strict//EN',
                                           'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd');
        $doc = $impl->createDocument($this->ccnamespaces[$this->rootns], null, $dtd);
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = true;
        $this->doc = $doc;
        parent::on_create();
    }

    public function on_save() {

        $rns = $this->ccnamespaces[$this->rootns];
        // Add the basic tags.
        $head = $this->append_new_element_ns($this->root, $rns, 'head');
        $this->append_new_attribute_ns($head, $rns, 'profile', 'http://dublincore.org/documents/dc-html/');

        // Linking Dublin Core Metadata 1.1.
        $link_dc = $this->append_new_element_ns($head, $rns, 'link');
        $this->append_new_attribute_ns($link_dc, $rns, 'rel', 'schema.DC');
        $this->append_new_attribute_ns($link_dc, $rns, 'href', 'http://purl.org/dc/elements/1.1/');
        $link_dcterms = $this->append_new_element_ns($head, $rns, 'link');
        $this->append_new_attribute_ns($link_dcterms, $rns, 'rel', 'schema.DCTERMS');
        $this->append_new_attribute_ns($link_dcterms, $rns, 'href', 'http://purl.org/dc/terms/');
        // Content type.
        $meta_type = $this->append_new_element_ns($head, $rns, 'meta');
        $this->append_new_attribute_ns($meta_type, $rns, 'name', 'DC.type');
        $this->append_new_attribute_ns($meta_type, $rns, 'scheme', 'DCTERMS.DCMIType');
        $this->append_new_attribute_ns($meta_type, $rns, 'content', 'Text');

        // Content description.
        if (!empty($this->intro)) {
            $meta_description = $this->append_new_element_ns($head, $rns, 'meta');
            $this->append_new_attribute_ns($meta_description, $rns, 'name', 'DC.description');
            $this->append_new_attribute_ns($meta_description, $rns, 'content', $this->intro);
        }

        $meta = $this->append_new_element_ns($head, $rns, 'meta');
        $this->append_new_attribute_ns($meta, $rns, 'http-equiv', 'Content-type');
        $this->append_new_attribute_ns($meta, $rns, 'content', 'text/html; charset=UTF-8');
        // Set the title.
        $title = $this->append_new_element_ns($head, $rns, 'title', $this->title);
        $body = $this->append_new_element_ns($this->root, $rns, 'body');
        // We are unable to use DOM for embedding HTML due to numerous content errors.
        // Therefore we place a dummy tag that will be later replaced with the real content.
        $this->append_new_element_ns($body, $rns, 'div', '##REPLACE##');

        return true;
    }

    public function saveTo($fname) {
        $result = $this->on_save();
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
}