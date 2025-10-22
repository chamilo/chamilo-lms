<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_organization.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIItem;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIOrganization;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Utils\CcHelpers;

/**
 * Organization Class.
 */
class CcOrganization implements CcIOrganization
{
    public $title;
    public $identifier;
    public $structure;
    public $itemlist;
    private $metadata;
    private $sequencing;

    public function __construct($node = null, $doc = null)
    {
        if (\is_object($node) && \is_object($doc)) {
            $this->processOrganization($node, $doc);
        } else {
            $this->initNew();
        }
    }

    /**
     * Add one Item into the Organization.
     */
    public function addItem(CcIItem &$item): void
    {
        if (null === $this->itemlist) {
            $this->itemlist = [];
        }
        $this->itemlist[$item->identifier] = $item;
    }

    /**
     * Add new Item into the Organization.
     *
     * @param string $title
     *
     * @return CcIItem
     */
    public function addNewItem($title = '')
    {
        $nitem = new CcItem();
        $nitem->title = $title;
        $this->addItem($nitem);

        return $nitem;
    }

    public function hasItems()
    {
        return \is_array($this->itemlist) && (\count($this->itemlist) > 0);
    }

    public function attrValue(&$nod, $name, $ns = null)
    {
        return null === $ns ?
             ($nod->hasAttribute($name) ? $nod->getAttribute($name) : null) :
             ($nod->hasAttributeNS($ns, $name) ? $nod->getAttributeNS($ns, $name) : null);
    }

    public function processOrganization(&$node, &$doc): void
    {
        $this->identifier = $this->attrValue($node, 'identifier');
        $this->structure = $this->attrValue($node, 'structure');
        $this->title = '';
        $nlist = $node->getElementsByTagName('title');
        if (\is_object($nlist) && ($nlist->length > 0)) {
            $this->title = $nlist->item(0)->nodeValue;
        }
        $nlist = $doc->nodeList("//imscc:organization[@identifier='".$this->identifier."']/imscc:item");
        $this->itemlist = [];
        foreach ($nlist as $item) {
            $this->itemlist[$item->getAttribute('identifier')] = new CcItem($item, $doc);
        }
        $this->isempty = false;
    }

    public function initNew(): void
    {
        $this->title = null;
        $this->identifier = CcHelpers::uuidgen('O_');
        $this->structure = 'rooted-hierarchy';
        $this->itemlist = null;
        $this->metadata = null;
        $this->sequencing = null;
    }

    public function uuidgen()
    {
        $uuid = \sprintf('%04x%04x', mt_rand(0, 65535), mt_rand(0, 65535));

        return strtoupper(trim($uuid));
    }
}
