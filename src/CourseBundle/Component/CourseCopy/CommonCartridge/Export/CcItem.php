<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIItem;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Utils\CcHelpers;
use Exception;

/**
 * Item Class.
 */
class CcItem implements CcIItem
{
    public $identifier;
    public $identifierref;
    public $isvisible;
    public $title;
    public $parameters;
    public $childitems;
    private $parentItem;
    private $isempty = true;

    public function __construct($node = null, $doc = null)
    {
        if (\is_object($node)) {
            $clname = $node::class;
            if ('CcResource' == $clname) {
                $this->initNewItem();
                $this->identifierref = $node->identifier;
                $this->title = \is_string($doc) && (!empty($doc)) ? $doc : 'item';
            } elseif ('CcManifest' == $clname) {
                $this->initNewItem();
                $this->identifierref = $node->manifestID();
                $this->title = \is_string($doc) && (!empty($doc)) ? $doc : 'item';
            } elseif (\is_object($doc)) {
                $this->processItem($node, $doc);
            } else {
                $this->initNewItem();
            }
        } else {
            $this->initNewItem();
        }
    }

    public function attrValue(&$nod, $name, $ns = null)
    {
        return null === $ns ?
            ($nod->hasAttribute($name) ? $nod->getAttribute($name) : null) :
            ($nod->hasAttributeNS($ns, $name) ? $nod->getAttributeNS($ns, $name) : null);
    }

    public function processItem(&$node, &$doc): void
    {
        $this->identifier = $this->attrValue($node, 'identifier');
        $this->structure = $this->attrValue($node, 'structure');
        $this->identifierref = $this->attrValue($node, 'identifierref');
        $atr = $this->attrValue($node, 'isvisible');
        $this->isvisible = null === $atr ? true : $atr;
        $nlist = $node->getElementsByTagName('title');
        if (\is_object($nlist) && ($nlist->length > 0)) {
            $this->title = $nlist->item(0)->nodeValue;
        }
        $nlist = $doc->nodeList("//imscc:item[@identifier='".$this->identifier."']/imscc:item");
        if ($nlist->length > 0) {
            $this->childitems = [];
            foreach ($nlist as $item) {
                $key = $this->attrValue($item, 'identifier');
                $this->childitems[$key] = new self($item, $doc);
            }
        }
        $this->isempty = false;
    }

    /**
     * Add one Child Item.
     */
    public function addChildItem(CcIItem &$item): void
    {
        if (null === $this->childitems) {
            $this->childitems = [];
        }
        $this->childitems[$item->identifier] = $item;
    }

    /**
     * Add new child Item.
     *
     * @param string $title
     *
     * @return CcIItem
     */
    public function add_new_child_item($title = '')
    {
        $sc = new self();
        $sc->title = $title;
        $this->addChildItem($sc);

        return $sc;
    }

    public function attachResource($resource): void
    {
        if ($this->hasChildItems()) {
            throw new Exception('Can not attach resource to item that contains other items!');
        }
        $resident = null;
        if (\is_string($resource)) {
            $resident = $resource;
        } elseif (\is_object($resource)) {
            $clname = $resource::class;
            if ('CcResource' == $clname) {
                $resident = $resource->identifier;
            } elseif ('CcManifest' == $clname) {
                $resident = $resource->manifestID();
            } else {
                throw new Exception('Unable to attach resource. Invalid object.');
            }
        }
        if (null === $resident || (empty($resident))) {
            throw new Exception('Resource must have valid identifier!');
        }
        $this->identifierref = $resident;
    }

    public function hasChildItems()
    {
        return \is_array($this->childitems) && (\count($this->childitems) > 0);
    }

    public function child_item($identifier)
    {
        return $this->hasChildItems() ? $this->childitems[$identifier] : null;
    }

    public function initClean(): void
    {
        $this->identifier = null;
        $this->isvisible = null;
        $this->title = null;
        $this->parameters = null;
        $this->childitems = null;
        $this->parentItem = null;
        $this->isempty = true;
    }

    public function initNewItem(): void
    {
        $this->identifier = CcHelpers::uuidgen('I_');
        $this->isvisible = true; // default is true
        $this->title = null;
        $this->parameters = null;
        $this->childitems = null;
        $this->parentItem = null;
        $this->isempty = false;
    }
}
