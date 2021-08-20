<?php
/* For licensing terms, see /license.txt */

class CcAssesmentItemmetadata extends CcQuestionMetadataBase
{
    public function addMetadata($object)
    {
        $this->metadata[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::itemmetadata);
        if (!empty($this->metadata)) {
            foreach ($this->metadata as $metaitem) {
                $metaitem->generate($doc, $node, $namespace);
            }
        }
    }
}
