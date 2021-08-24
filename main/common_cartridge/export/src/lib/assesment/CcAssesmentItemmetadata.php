<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentItemmetadata extends CcQuestionMetadataBase
{
    public function addMetadata($object)
    {
        $this->metadata[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::ITEMMETADATA);
        if (!empty($this->metadata)) {
            foreach ($this->metadata as $metaitem) {
                $metaitem->generate($doc, $node, $namespace);
            }
        }
    }
}
