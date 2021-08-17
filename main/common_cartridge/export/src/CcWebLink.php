<?php
/* For licensing terms, see /license.txt */

class CcWebLink extends CcGeneralFile
{

    protected $rootns = 'wl';
    protected $rootname = 'webLink';
    protected $ccnamespaces = array('wl'  => 'http://www.imsglobal.org/xsd/imsccv1p3/imswl_v1p3',
                                    'xsi' => 'http://www.w3.org/2001/XMLSchema-instance');
    protected $ccnsnames = array('wl' => 'http://www.imsglobal.org/profile/cc/ccv1p3/ccv1p3_imswl_v1p3.xsd');

    const deafultname = 'weblink.xml';

    protected $url = null;
    protected $title = null;
    protected $href = null;
    protected $target = '_self';
    protected $window_features = null;

    /**
     *
     * Set the url title
     * @param string $title
     */
    public function set_title($title)
    {
        $this->title = self::safexml($title);
    }

    /**
     *
     * Set the url specifics
     * @param string $url
     * @param string $target
     * @param string $window_features
     */
    public function set_url($url, $target = '_self', $window_features = null)
    {
        $this->url = $url;
        $this->target = $target;
        $this->window_features = $window_features;
    }

    protected function on_save()
    {
        $rns = $this->ccnamespaces[$this->rootns];
        $this->append_new_element_ns($this->root, $rns, 'title', $this->title);
        $url = $this->append_new_element_ns($this->root, $rns, 'url');
        $this->append_new_attribute_ns($url, $rns, 'href', $this->url);
        if (!empty($this->target)) {
            $this->append_new_attribute_ns($url, $rns, 'target', $this->target);
        }
        if (!empty($this->window_features)) {
            $this->append_new_attribute_ns($url, $rns, 'windowFeatures', $this->window_features);
        }
        return true;
    }
}

