<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/xmlbase.php under GNU/GPL license */

/**
 * Base XML class.
 */
class XMLGenericDocument
{
    /**
     * Document.
     *
     * @var DOMDocument
     */
    public $doc = null;
    /**
     * Xpath.
     *
     * @var DOMXPath
     */
    protected $dxpath = null;
    protected $filename;
    private $charset;
    private $filepath;
    private $isloaded = false;
    private $arrayPrefixNS = [];
    private $isHtml = false;

    public function __construct($ch = 'UTF-8', $validatenow = true)
    {
        $this->charset = $ch;
        $this->documentInit();
        $this->doc->validateOnParse = $validatenow;
    }

    public function __destruct()
    {
        $this->dxpath = null;
        $this->doc = null;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public static function safexml($value)
    {
        $result = htmlspecialchars(html_entity_decode($value, ENT_QUOTES, 'UTF-8'),
                                   ENT_NOQUOTES,
                                   'UTF-8',
                                   false);

        return $result;
    }

    public function viewXML()
    {
        return $this->doc->saveXML();
    }

    public function registerNS($prefix, $nsuri)
    {
        $this->arrayPrefixNS[$prefix] = $nsuri;
    }

    public function load($fname)
    {
        // Sine xml will remain loaded should the repeated load fail we should recreate document to be empty.
        $this->documentInit(false);
        $this->isloaded = $this->doc->load($fname);
        if ($this->isloaded) {
            $this->filename = $fname;
            $this->processPath();
            $this->isHtml = false;
        }

        return $this->onLoad();
    }

    public function loadUrl($url)
    {
        $this->documentInit();
        $this->isloaded = true;
        $this->doc->loadXML(file_get_contents($url));
        $this->isHtml = false;

        return $this->onLoad();
    }

    public function loadHTML($content)
    {
        $this->documentInit();
        $this->doc->validateOnParse = false;
        $this->isloaded = true;
        $this->doc->loadHTML($content);
        $this->isHtml = true;

        return $this->onLoad();
    }

    public function loadXML($content)
    {
        $this->documentInit();
        $this->doc->validateOnParse = false;
        $this->isloaded = true;
        $this->doc->load($content);
        $this->isHtml = true;

        return $this->onLoad();
    }

    public function loadHTMLFile($fname)
    {
        // Sine xml will remain loaded should the repeated load fail
        // we should recreate document to be empty.
        $this->documentInit();
        $this->doc->validateOnParse = false;
        $this->isloaded = $this->doc->loadHTMLFile($fname);
        if ($this->isloaded) {
            $this->filename = $fname;
            $this->processPath();
            $this->isHtml = true;
        }

        return $this->onLoad();
    }

    public function loadXMLFile($fname)
    {
        // Sine xml will remain loaded should the repeated load fail
        // we should recreate document to be empty.
        $this->documentInit();
        $this->doc->validateOnParse = false;
        $this->isloaded = $this->doc->load($fname);
        if ($this->isloaded) {
            $this->filename = $fname;
            $this->processPath();
            $this->isHtml = true;
        }

        return $this->onLoad();
    }

    public function loadString($content)
    {
        $this->doc = new DOMDocument("1.0", $this->charset);
        $content = '<virtualtag>'.$content.'</virtualtag>';
        $this->doc->loadXML($content);

        return true;
    }

    public function save()
    {
        $this->saveTo($this->filename);
    }

    public function saveTo($fname)
    {
        $status = false;
        if ($this->onSave()) {
            if ($this->isHtml) {
                $this->doc->saveHTMLFile($fname);
            } else {
                $this->doc->save($fname);
            }
            $this->filename = $fname;
            $this->processPath();
            $status = true;
        }

        return $status;
    }

    public function validate()
    {
        return $this->doc->validate();
    }

    public function attributeValue($path, $attrname, $node = null)
    {
        $this->chkxpath();
        $result = null;
        $resultlist = null;
        if (is_null($node)) {
            $resultlist = $this->dxpath->query($path);
        } else {
            $resultlist = $this->dxpath->query($path, $node);
        }
        if (is_object($resultlist) && ($resultlist->length > 0) && $resultlist->item(0)->hasAttribute($attrname)) {
            $result = $resultlist->item(0)->getAttribute($attrname);
        }

        return $result;
    }

    /**
     * Get's text value of the node based on xpath query.
     *
     * @param string  $path
     * @param DOMNode $node
     * @param int     $count
     *
     * @return string
     */
    public function nodeValue($path, $node = null, $count = 1)
    {
        $nd = $this->node($path, $node, $count);

        return $this->nodeTextValue($nd);
    }

    /**
     * Get's text value of the node.
     *
     * @param DOMNode $node
     *
     * @return string
     */
    public function nodeTextValue($node)
    {
        $result = '';
        if (is_object($node)) {
            if ($node->hasChildNodes()) {
                $chnodesList = $node->childNodes;
                $types = [XML_TEXT_NODE, XML_CDATA_SECTION_NODE];
                foreach ($chnodesList as $chnode) {
                    if (in_array($chnode->nodeType, $types)) {
                        $result .= $chnode->wholeText;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get the nodes from a path.
     *
     * @param string  $path
     * @param DOMNode $nd
     * @param int     $count
     *
     * @return DOMNode
     */
    public function node($path, $nd = null, $count = 1)
    {
        $result = null;
        $resultlist = $this->nodeList($path, $nd);
        if (is_object($resultlist) && ($resultlist->length > 0)) {
            $result = $resultlist->item($count - 1);
        }

        return $result;
    }

    /**
     * Get a list of nodes from a path.
     *
     * @param string  $path
     * @param DOMNode $node
     *
     * @return DOMNodeList
     */
    public function nodeList($path, $node = null)
    {
        $this->chkxpath();
        $resultlist = null;
        if (is_null($node)) {
            $resultlist = $this->dxpath->query($path);
        } else {
            $resultlist = $this->dxpath->query($path, $node);
        }

        return $resultlist;
    }

    /**
     * Create new attribute.
     *
     * @param string $namespace
     * @param string $name
     * @param string $value
     *
     * @return DOMAttr
     */
    public function createAttributeNs($namespace, $name, $value = null)
    {
        $result = $this->doc->createAttributeNS($namespace, $name);
        if (!is_null($value)) {
            $result->nodeValue = $value;
        }

        return $result;
    }

    /**
     * Create new attribute.
     *
     * @param string $name
     * @param string $value
     *
     * @return DOMAttr
     */
    public function createAttribute($name, $value = null)
    {
        $result = $this->doc->createAttribute($name);
        if (!is_null($value)) {
            $result->nodeValue = $value;
        }

        return $result;
    }

    /**
     * Adds new node.
     *
     * @param string $namespace
     * @param string $name
     * @param string $value
     *
     * @return DOMNode
     */
    public function appendNewElementNs(DOMNode &$parentnode, $namespace, $name, $value = null)
    {
        $newnode = null;
        if (is_null($value)) {
            $newnode = $this->doc->createElementNS($namespace, $name);
        } else {
            $newnode = $this->doc->createElementNS($namespace, $name, $value);
        }

        return $parentnode->appendChild($newnode);
    }

    /**
     * New node with CDATA content.
     *
     * @param string $namespace
     * @param string $name
     * @param string $value
     */
    public function appendNewElementNsCdata(DOMNode &$parentnode, $namespace, $name, $value = null)
    {
        $newnode = $this->doc->createElementNS($namespace, $name);
        if (!is_null($value)) {
            $cdata = $this->doc->createCDATASection($value);
            $newnode->appendChild($cdata);
        }

        return $parentnode->appendChild($newnode);
    }

    /**
     * Adds new node.
     *
     * @param string $name
     * @param string $value
     *
     * @return DOMNode
     */
    public function appendNewElement(DOMNode &$parentnode, $name, $value = null)
    {
        $newnode = null;
        if (is_null($value)) {
            $newnode = $this->doc->createElement($name);
        } else {
            $newnode = $this->doc->createElement($name, $value);
        }

        return $parentnode->appendChild($newnode);
    }

    /**
     * Adds new attribute.
     *
     * @param string $name
     * @param string $value
     *
     * @return DOMNode
     */
    public function appendNewAttribute(DOMNode &$node, $name, $value = null)
    {
        return $node->appendChild($this->createAttribute($name, $value));
    }

    /**
     * Adds new attribute.
     *
     * @param string $namespace
     * @param string $name
     * @param string $value
     *
     * @return DOMNode
     */
    public function appendNewAttributeNs(DOMNode &$node, $namespace, $name, $value = null)
    {
        return $node->appendChild($this->createAttributeNs($namespace, $name, $value));
    }

    public function fileName()
    {
        return $this->filename;
    }

    public function filePath()
    {
        return $this->filepath;
    }

    public function resetXpath()
    {
        $this->dxpath = null;
        $this->chkxpath();
    }

    protected function onLoad()
    {
        return $this->isloaded;
    }

    protected function onSave()
    {
        return true;
    }

    protected function onCreate()
    {
        return true;
    }

    protected function processPath()
    {
        $path_parts = pathinfo($this->filename);
        $this->filepath = array_key_exists('dirname', $path_parts) ? $path_parts['dirname']."/" : '';
    }

    private function documentInit($withonCreate = true)
    {
        $hg = false;
        if ($this->isloaded) {
            $guardstate = $this->doc->validateOnParse;
            $hg = true;
            unset($this->dxpath);
            unset($this->doc);
            $this->isloaded = false;
        }
        $this->doc = new DOMDocument("1.0", $this->charset);
        $this->doc->strictErrorChecking = true;
        if ($hg) {
            $this->doc->validateOnParse = $guardstate;
        }
        $this->doc->formatOutput = true;
        $this->doc->preserveWhiteSpace = true;
        if ($withonCreate) {
            $this->onCreate();
        }
    }

    private function chkxpath()
    {
        if (!isset($this->dxpath) || is_null($this->dxpath)) {
            $this->dxpath = new DOMXPath($this->doc);
            foreach ($this->arrayPrefixNS as $nskey => $nsuri) {
                $this->dxpath->registerNamespace($nskey, $nsuri);
            }
        }
    }
}
