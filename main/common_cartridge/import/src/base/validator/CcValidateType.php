<?php
/* For licensing terms, see /license.txt */

class CcValidateType
{
    const manifest_validator1 = 'cclibxml2validator.xsd';
    const assesment_validator1 = '/domainProfile_4/ims_qtiasiv1p2_localised.xsd';
    const discussion_validator1 = '/domainProfile_6/imsdt_v1p0_localised.xsd';
    const weblink_validator1 = '/domainProfile_5/imswl_v1p0_localised.xsd';

    const manifest_validator11 = 'cc11libxml2validator.xsd';
    const blti_validator11 = 'imslticc_v1p0p1.xsd';
    const assesment_validator11 = 'ccv1p1_qtiasiv1p2p1_v1p0.xsd';
    const discussion_validator11 = 'ccv1p1_imsdt_v1p1.xsd';
    const weblink_validator11 = 'ccv1p1_imswl_v1p1.xsd';

    const manifest_validator13 = 'cc13libxml2validator.xsd';
    const blti_validator13 = 'imslticc_v1p3.xsd';
    const assesment_validator13 = 'ccv1p3_qtiasiv1p2p1_v1p0.xsd';
    const discussion_validator13 = 'ccv1p3_imsdt_v1p3.xsd';
    const weblink_validator13 = 'ccv1p3_imswl_v1p3.xsd';

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
