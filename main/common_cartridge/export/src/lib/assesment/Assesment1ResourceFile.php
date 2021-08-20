<?php
/* For licensing terms, see /license.txt */

class Assesment1ResourceFile extends CcGeneralFile
{
    const deafultname = 'assesment.xml';

    protected $rootns = 'xmlns';
    protected $rootname = CcQtiTags::questestinterop;
    protected $ccnamespaces = ['xmlns' => 'http://www.imsglobal.org/xsd/ims_qtiasiv1p2',
        'xsi' => 'http://www.w3.org/2001/XMLSchema-instance', ];
    protected $ccnsnames = ['xmlns' => 'http://www.imsglobal.org/profile/cc/ccv1p0/derived_schema/domainProfile_4/ims_qtiasiv1p2_localised.xsd'];
    protected $assessmentTitle = 'Untitled';
    protected $metadata = null;
    protected $rubric = null;
    protected $presentationMaterial = null;
    protected $section = null;

    public function setMetadata(CcAssesmentMetadata $object)
    {
        $this->metadata = $object;
    }

    public function setRubric(CcAssesmentRubricBase $object)
    {
        $this->rubric = $object;
    }

    public function setPresentationMaterial(CcAssesmentPresentationMaterialBase $object)
    {
        $this->presentationMaterial = $object;
    }

    public function setSection(CcAssesmentSection $object)
    {
        $this->section = $object;
    }

    public function setTitle($value)
    {
        $this->assessmentTitle = self::safexml($value);
    }

    protected function onSave()
    {
        $rns = $this->ccnamespaces[$this->rootns];
        //root assesment element - required
        $assessment = $this->appendNewElementNs($this->root, $rns, CcQtiTags::assessment);
        $this->appendNewAttributeNs($assessment, $rns, CcQtiTags::ident, CcHelpers::uuidgen('QDB_'));
        $this->appendNewAttributeNs($assessment, $rns, CcQtiTags::title, $this->assessmentTitle);

        //metadata - optional
        if (!empty($this->metadata)) {
            $this->metadata->generate($this, $assessment, $rns);
        }

        //rubric - optional
        if (!empty($this->rubric)) {
            $this->rubric->generate($this, $assessment, $rns);
        }

        //presentation_material - optional
        if (!empty($this->presentationMaterial)) {
            $this->presentationMaterial->generate($this, $assessment, $rns);
        }

        //section - required
        if (!empty($this->section)) {
            $this->section->generate($this, $assessment, $rns);
        }

        return true;
    }
}
