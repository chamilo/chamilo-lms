<?php
/* For licensing terms, see /license.txt */

final class ErrorMessages {
    /**
     *
     * @static ErrorMessages
     */
    private static $instance = null;
    private function __construct(){}
    private function __clone(){}
    /**
     * @return ErrorMessages
     */
    public static function instance() {
        if (empty(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c();
        }
        return self::$instance;
    }

    /**
     * @var array
     */
    private $items = array();

    /**
     * @param string $msg
     */
    public function add($msg) {
        if (!empty($msg)) {
            $this->items[] = $msg;
        }
    }

    /**
     * @return array
     */
    public function errors() {
        $this->items;
    }

    /**
     * Empties the error content
     */
    public function reset() {
        $this->items = array();
    }

    /**
     * @param boolean $web
     * @return string
     */
    public function toString($web = false) {
        $result = '';
        if ($web) {
            $result .= '<ol>'.PHP_EOL;
        }
        foreach ($this->items as $error) {
            if ($web) {
                $result .= '<li>';
            }

            $result .= $error.PHP_EOL;

            if ($web) {
                $result .= '</li>'.PHP_EOL;
            }
        }
        if ($web) {
            $result .= '</ol>'.PHP_EOL;
        }
        return $result;
    }

    /**
     * Casting to string method
     * @return string
     */
    public function __toString() {
        return $this->toString(false);
    }

}

final class LibxmlErrorsMgr {
    /**
     * @var boolean
     */
    private $previous = false;

    /**
     * @param boolean $reset
     */
    public function __construct($reset=false){
        if ($reset) {
            ErrorMessages::instance()->reset();
        }
        $this->previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
    }

    private function collectErrors ($filename=''){
        $errors = libxml_get_errors();
        static $error_types = array(
        LIBXML_ERR_ERROR => 'Error'
        ,LIBXML_ERR_FATAL => 'Fatal Error'
        ,LIBXML_ERR_WARNING => 'Warning'
        );
        $result = array();
        foreach($errors as $error){
            $add = '';
            if (!empty($filename)) {
                $add = " in {$filename}";
            } elseif (!empty($error->file)) {
                $add = " in {$error->file}";
            }
            $line = '';
            if (!empty($error->line)) {
                $line = " at line {$error->line}";
            }
            $err = "{$error_types[$error->level]}{$add}: {$error->message}{$line}";
            ErrorMessages::instance()->add($err);
        }
        libxml_clear_errors();
        return $result;
    }

    public function __destruct(){
        $this->collectErrors();
        if (!$this->previous) {
            libxml_use_internal_errors($this->previous);
        }
    }

    public function collect() {
        $this->collectErrors();
    }
}


function validateXml($xml, $schema) {
    $result = false;
    $manifest_file = realpath($xml);
    $schema_file = realpath($schema);
    if (empty($manifest_file) || empty($schema_file)) {
        return false;
    }

    $xml_error = new LibxmlErrorsMgr();
    $manifest = new DOMDocument();
    $doc->validateOnParse = false;
    $result = $manifest->load($manifest_file, LIBXML_NONET) &&
              $manifest->schemaValidate($schema_file);

    return $result;
}

class CcValidateType {
    const manifest_validator1   = 'cclibxml2validator.xsd'                       ;
    const assesment_validator1  = '/domainProfile_4/ims_qtiasiv1p2_localised.xsd';
    const discussion_validator1 = '/domainProfile_6/imsdt_v1p0_localised.xsd'    ;
    const weblink_validator1    = '/domainProfile_5/imswl_v1p0_localised.xsd'    ;

    const manifest_validator11   = 'cc11libxml2validator.xsd'    ;
    const blti_validator11       = 'imslticc_v1p0p1.xsd'         ;
    const assesment_validator11  = 'ccv1p1_qtiasiv1p2p1_v1p0.xsd';
    const discussion_validator11 = 'ccv1p1_imsdt_v1p1.xsd'       ;
    const weblink_validator11    = 'ccv1p1_imswl_v1p1.xsd'       ;

    const manifest_validator13   = 'cc13libxml2validator.xsd'    ;
    const blti_validator13       = 'imslticc_v1p3.xsd'         ;
    const assesment_validator13  = 'ccv1p3_qtiasiv1p2p1_v1p0.xsd';
    const discussion_validator13 = 'ccv1p3_imsdt_v1p3.xsd'       ;
    const weblink_validator13    = 'ccv1p3_imswl_v1p3.xsd'       ;

    /**
     * @var string
     */
    protected $type = null;

    /**
     * @var string
     */
    protected $location = null;

    public function __construct($type, $location){
        $this->type = $type;
        $this->location = $location;
    }

    /**
     * Validates the item
     * @param  string $element - File path for the xml
     * @return boolean
     */
    public function validate($element) {
        $this->last_error = null;
        $celement   = realpath($element);
        $cvalidator = realpath($this->location.DIRECTORY_SEPARATOR.$this->type);
        $result = (empty($celement) || empty($cvalidator));
        if (!$result) {
            $xml_error = new LibxmlErrorsMgr();
            $doc = new DOMDocument();
            $doc->validateOnParse = false;
            $result = $doc->load($celement, LIBXML_NONET) &&
                      $doc->schemaValidate($cvalidator);
        }
        return $result;
    }

}

class ManifestValidator extends CcValidateType {
    public function __construct($location){
        parent::__construct(self::manifest_validator13, $location);
    }
}

class Manifest10Validator extends CcValidateType {
    public function __construct($location){
        parent::__construct(self::manifest_validator1, $location);
    }
}

class BltiValidator extends CcValidateType {
    public function __construct($location){
        parent::__construct(self::blti_validator13, $location);
    }
}

class AssesmentValidator extends CcValidateType {
    public function __construct($location){
        parent::__construct(self::assesment_validator13, $location);
    }
}

class DiscussionValidator extends CcValidateType {
    public function __construct($location){
        parent::__construct(self::discussion_validator13, $location);
    }
}

class WeblinkValidator extends CcValidateType {
    public function __construct($location){
        parent::__construct(self::weblink_validator13, $location);
    }
}
