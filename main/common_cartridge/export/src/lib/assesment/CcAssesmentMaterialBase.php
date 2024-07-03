<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

abstract class CcAssesmentMaterialBase extends CcQuestionMetadataBase
{
    /**
     * @var mixed
     */
    protected $mattag = null;
    protected $tagname = null;

    public function setMattext(CcAssesmentMattext $object)
    {
        $this->setTagValue($object);
    }

    public function setMatref(CcAssesmentMatref $object)
    {
        $this->setTagValue($object);
    }

    public function setMatbreak(CcAssesmentMatbreak $object)
    {
        $this->setTagValue($object);
    }

    public function setLang($value)
    {
        $this->setSettingWns(CcQtiTags::XML_LANG, CcXmlNamespace::XML, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $material = $doc->appendNewElementNs($item, $namespace, $this->tagname);
        $this->generateAttributes($doc, $material, $namespace);
        if (!empty($this->mattag)) {
            $this->mattag->generate($doc, $material, $namespace);
        }

        return $material;
    }

    protected function setTagValue($object)
    {
        $this->mattag = $object;
    }
}
