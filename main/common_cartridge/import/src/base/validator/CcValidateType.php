<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/validator.php under GNU/GPL license */

class CcValidateType
{
    public const MANIFEST_VALIDATOR1 = 'cclibxml2validator.xsd';
    public const ASSESMENT_VALIDATOR1 = '/domainProfile_4/ims_qtiasiv1p2_localised.xsd';
    public const DISCUSSION_VALIDATOR1 = '/domainProfile_6/imsdt_v1p0_localised.xsd';
    public const WEBLINK_VALIDATOR1 = '/domainProfile_5/imswl_v1p0_localised.xsd';

    public const MANIFEST_VALIDATOR11 = 'cc11libxml2validator.xsd';
    public const BLTI_VALIDATOR11 = 'imslticc_v1p0p1.xsd';
    public const ASSESMENT_VALIDATOR11 = 'ccv1p1_qtiasiv1p2p1_v1p0.xsd';
    public const DISCUSSION_VALIDATOR11 = 'ccv1p1_imsdt_v1p1.xsd';
    public const WEBLINK_VALIDATOR11 = 'ccv1p1_imswl_v1p1.xsd';

    public const MANIFEST_VALIDATOR13 = 'cc13libxml2validator.xsd';
    public const BLTI_VALIDATOR13 = 'imslticc_v1p3.xsd';
    public const ASSESMENT_VALIDATOR13 = 'ccv1p3_qtiasiv1p2p1_v1p0.xsd';
    public const DISCUSSION_VALIDATOR13 = 'ccv1p3_imsdt_v1p3.xsd';
    public const WEBLINK_VALIDATOR13 = 'ccv1p3_imswl_v1p3.xsd';

    /**
     * @var string
     */
    protected $type = null;

    /**
     * @var string
     */
    protected $location = null;

    public function __construct($type, $location)
    {
        $this->type = $type;
        $this->location = $location;
    }

    /**
     * Validates the item.
     *
     * @param string $element - File path for the xml
     *
     * @return bool
     */
    public function validate($element)
    {
        $this->last_error = null;
        $celement = realpath($element);
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
