<?php
/* For licensing terms, see /license.txt */

class CcForum extends CcGeneralFile
{
    protected $rootns = 'dt';
    protected $rootname = 'topic';
    protected $ccnamespaces = array('dt'  => 'http://www.imsglobal.org/xsd/imsccv1p3/imsdt_v1p3',
                                    'xsi' => 'http://www.w3.org/2001/XMLSchema-instance');
    protected $ccnsnames = array('dt' => 'http://www.imsglobal.org/profile/cc/ccv1p3/ccv1p3_imsdt_v1p3.xsd');
    
    const deafultname = 'discussion.xml';
    protected $title = null;
    protected $text_type = 'text/plain';
    protected $text = null;
    protected $attachments = array();

    public function set_title($title) {
        $this->title = self::safexml($title);
    }

    public function set_text($text, $type='text/plain') {
        $this->text = self::safexml($text);
        $this->text_type = $type;
    }

    public function set_attachments(array $attachments) {
        $this->attachments = $attachments;
    }
    
    protected function on_save() {
        $rns = $this->ccnamespaces[$this->rootns];
        $this->append_new_element_ns($this->root, $rns, 'title', $this->title);
        $text = $this->append_new_element_ns($this->root, $rns, 'text', $this->text);
        $this->append_new_attribute_ns($text, $rns, 'texttype', $this->text_type);
        if (!empty($this->attachments)) {
            $attachments = $this->append_new_element_ns($this->root, $rns, 'attachments');
            foreach ($this->attachments as $value) {
                $att = $this->append_new_element_ns($attachments, $rns, 'attachment');
                $this->append_new_attribute_ns($att, $rns, 'href', $value);
            }
        }
        return true;
    }

}


