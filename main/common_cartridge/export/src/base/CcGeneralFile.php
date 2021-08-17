<?php
/* For licensing terms, see /license.txt */

class CcGeneralFile extends XMLGenericDocument
{
    /**
     *
     * Root element
     * @var DOMElement
     */
    protected $root = null;
    protected $rootns = null;
    protected $rootname = null;
    protected $ccnamespaces = array();
    protected $ccnsnames = array();

    public function __construct()
    {
        parent::__construct();

        foreach ($this->ccnamespaces as $key => $value){
            $this->registerNS($key,$value);
        }
    }


    protected function on_create()
    {
        $rootel = $this->append_new_element_ns($this->doc,
                                               $this->ccnamespaces[$this->rootns],
                                               $this->rootname);
        //add all namespaces
        foreach ($this->ccnamespaces as $key => $value) {
            $dummy_attr = "{$key}:dummy";
            $this->doc->createAttributeNS($value,$dummy_attr);
        }

        // add location of schemas
        $schemaLocation='';
        foreach ($this->ccnsnames as $key => $value) {
            $vt = empty($schemaLocation) ? '' : ' ';
            $schemaLocation .= $vt.$this->ccnamespaces[$key].' '.$value;
        }

        if (!empty($schemaLocation) && isset($this->ccnamespaces['xsi'])) {
            $this->append_new_attribute_ns($rootel,
                                           $this->ccnamespaces['xsi'],
                                           'xsi:schemaLocation',
                                            $schemaLocation);
        }

        $this->root = $rootel;
    }

}
