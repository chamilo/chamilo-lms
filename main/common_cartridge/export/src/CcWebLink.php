<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_weblink.php under GNU/GPL license */

class CcWebLink extends CcGeneralFile
{
    public const DEAFULTNAME = 'weblink.xml';

    protected $rootns = 'wl';
    protected $rootname = 'webLink';
    protected $ccnamespaces = ['wl' => 'http://www.imsglobal.org/xsd/imsccv1p3/imswl_v1p3',
                                    'xsi' => 'http://www.w3.org/2001/XMLSchema-instance', ];
    protected $ccnsnames = ['wl' => 'http://www.imsglobal.org/profile/cc/ccv1p3/ccv1p3_imswl_v1p3.xsd'];

    protected $url = null;
    protected $title = null;
    protected $href = null;
    protected $target = '_self';
    protected $windowFeatures = null;

    /**
     * Set the url title.
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = self::safexml($title);
    }

    /**
     * Set the url specifics.
     *
     * @param string $url
     * @param string $target
     * @param string $windowFeatures
     */
    public function setUrl($url, $target = '_self', $windowFeatures = null)
    {
        $this->url = $url;
        $this->target = $target;
        $this->windowFeatures = $windowFeatures;
    }

    protected function onSave()
    {
        $rns = $this->ccnamespaces[$this->rootns];
        $this->appendNewElementNs($this->root, $rns, 'title', $this->title);
        $url = $this->appendNewElementNs($this->root, $rns, 'url');
        $this->appendNewAttributeNs($url, $rns, 'href', $this->url);
        if (!empty($this->target)) {
            $this->appendNewAttributeNs($url, $rns, 'target', $this->target);
        }
        if (!empty($this->windowFeatures)) {
            $this->appendNewAttributeNs($url, $rns, 'windowFeatures', $this->windowFeatures);
        }

        return true;
    }
}
