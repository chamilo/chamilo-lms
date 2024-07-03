<?php
/* For licensing terms, see /license.txt */

/**
 * Item Class.
 */
class CcItem implements CcIItem
{
    public $identifier = null;
    public $identifierref = null;
    public $isvisible = null;
    public $title = null;
    public $parameters = null;
    public $childitems = null;
    private $parentItem = null;
    private $isempty = true;

    public function __construct($node = null, $doc = null)
    {
        if (is_object($node)) {
            $clname = get_class($node);
            if ($clname == 'CcResource') {
                $this->initNewItem();
                $this->identifierref = $node->identifier;
                $this->title = is_string($doc) && (!empty($doc)) ? $doc : 'item';
            } elseif ($clname == 'CcManifest') {
                $this->initNewItem();
                $this->identifierref = $node->manifestID();
                $this->title = is_string($doc) && (!empty($doc)) ? $doc : 'item';
            } elseif (is_object($doc)) {
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
        return is_null($ns) ?
            ($nod->hasAttribute($name) ? $nod->getAttribute($name) : null) :
            ($nod->hasAttributeNS($ns, $name) ? $nod->getAttributeNS($ns, $name) : null);
    }

    public function processItem(&$node, &$doc)
    {
        $this->identifier = $this->attrValue($node, "identifier");
        $this->structure = $this->attrValue($node, "structure");
        $this->identifierref = $this->attrValue($node, "identifierref");
        $atr = $this->attrValue($node, "isvisible");
        $this->isvisible = is_null($atr) ? true : $atr;
        $nlist = $node->getElementsByTagName('title');
        if (is_object($nlist) && ($nlist->length > 0)) {
            $this->title = $nlist->item(0)->nodeValue;
        }
        $nlist = $doc->nodeList("//imscc:item[@identifier='".$this->identifier."']/imscc:item");
        if ($nlist->length > 0) {
            $this->childitems = [];
            foreach ($nlist as $item) {
                $key = $this->attrValue($item, "identifier");
                $this->childitems[$key] = new CcItem($item, $doc);
            }
        }
        $this->isempty = false;
    }

    /**
     * Add one Child Item.
     */
    public function addChildItem(CcIItem &$item)
    {
        if (is_null($this->childitems)) {
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
        $sc = new CcItem();
        $sc->title = $title;
        $this->addChildItem($sc);

        return $sc;
    }

    public function attachResource($resource)
    {
        if ($this->hasChildItems()) {
            throw new Exception("Can not attach resource to item that contains other items!");
        }
        $resident = null;
        if (is_string($resource)) {
            $resident = $resource;
        } elseif (is_object($resource)) {
            $clname = get_class($resource);
            if ($clname == 'CcResource') {
                $resident = $resource->identifier;
            } elseif ($clname == 'CcManifest') {
                $resident = $resource->manifestID();
            } else {
                throw new Exception("Unable to attach resource. Invalid object.");
            }
        }
        if (is_null($resident) || (empty($resident))) {
            throw new Exception("Resource must have valid identifier!");
        }
        $this->identifierref = $resident;
    }

    public function hasChildItems()
    {
        return is_array($this->childitems) && (count($this->childitems) > 0);
    }

    public function child_item($identifier)
    {
        return $this->hasChildItems() ? $this->childitems[$identifier] : null;
    }

    public function initClean()
    {
        $this->identifier = null;
        $this->isvisible = null;
        $this->title = null;
        $this->parameters = null;
        $this->childitems = null;
        $this->parentItem = null;
        $this->isempty = true;
    }

    public function initNewItem()
    {
        $this->identifier = CcHelpers::uuidgen('I_');
        $this->isvisible = true; //default is true
        $this->title = null;
        $this->parameters = null;
        $this->childitems = null;
        $this->parentItem = null;
        $this->isempty = false;
    }
}
