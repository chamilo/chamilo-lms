<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcQuestionMetadataBase
{
    protected $metadata = [];

    /**
     * @param string $namespace
     */
    public function generateAttributes(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        foreach ($this->metadata as $attribute => $value) {
            if (!is_null($value)) {
                if (!is_array($value)) {
                    $doc->appendNewAttributeNs($item, $namespace, $attribute, $value);
                } else {
                    $ns = key($value);
                    $nval = current($value);
                    if (!is_null($nval)) {
                        $doc->appendNewAttributeNs($item, $ns, $attribute, $nval);
                    }
                }
            }
        }
    }

    /**
     * @param string $namespace
     */
    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $qtimetadata = $doc->appendNewElementNs($item, $namespace, CcQtiTags::QTIMETADATA);
        foreach ($this->metadata as $label => $entry) {
            if (!is_null($entry)) {
                $qtimetadatafield = $doc->appendNewElementNs($qtimetadata, $namespace, CcQtiTags::QTIMETADATAFIELD);
                $doc->appendNewElementNs($qtimetadatafield, $namespace, CcQtiTags::FIELDLABEL, $label);
                $doc->appendNewElementNs($qtimetadatafield, $namespace, CcQtiTags::FIELDENTRY, $entry);
            }
        }
    }

    /**
     * @param string $setting
     * @param mixed  $value
     */
    protected function setSetting($setting, $value = null)
    {
        $this->metadata[$setting] = $value;
    }

    /**
     * @param string $setting
     *
     * @return mixed
     */
    protected function getSetting($setting)
    {
        $result = null;
        if (array_key_exists($setting, $this->metadata)) {
            $result = $this->metadata[$setting];
        }

        return $result;
    }

    /**
     * @param string $setting
     * @param string $namespace
     * @param string $value
     */
    protected function setSettingWns($setting, $namespace, $value = null)
    {
        $this->metadata[$setting] = [$namespace => $value];
    }

    /**
     * @param string $setting
     * @param bool   $value
     */
    protected function enableSettingYesno($setting, $value = true)
    {
        $svalue = $value ? CcQtiValues::YES : CcQtiValues::NO;
        $this->setSetting($setting, $svalue);
    }
}
