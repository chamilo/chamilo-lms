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

    public function setTitle($title)
    {
        $this->title = self::safexml($title);
    }

    public function setText($text, $type='text/plain')
    {
        $this->text = self::safexml($text);
        $this->text_type = $type;
    }

    public function setAttachments(array $attachments)
    {
        $this->attachments = $attachments;
    }

    protected function onSave()
    {
        $rns = $this->ccnamespaces[$this->rootns];
        $this->appendNewElementNs($this->root, $rns, 'title', $this->title);
        $text = $this->appendNewElementNs($this->root, $rns, 'text', $this->text);
        $this->appendNewAttributeNs($text, $rns, 'texttype', $this->text_type);
        if (!empty($this->attachments)) {
            $attachments = $this->appendNewElementNs($this->root, $rns, 'attachments');
            foreach ($this->attachments as $value) {
                $att = $this->appendNewElementNs($attachments, $rns, 'attachment');
                $this->appendNewAttributeNs($att, $rns, 'href', $value);
            }
        }
        return true;
    }

}


