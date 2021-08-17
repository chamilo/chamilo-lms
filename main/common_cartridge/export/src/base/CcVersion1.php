<?php
/* For licensing terms, see /license.txt */


/**
 * Version 1 class of Common Cartridge
 *
 */
class CcVersion1 extends CcVersionBase
{
    const   webcontent          = 'webcontent';
    const   questionbank        = 'imsqti_xmlv1p2/imscc_xmlv1p0/question-bank';
    const   assessment          = 'imsqti_xmlv1p2/imscc_xmlv1p0/assessment';
    const   associatedcontent   = 'associatedcontent/imscc_xmlv1p0/learning-application-resource';
    const   discussiontopic     = 'imsdt_xmlv1p0';
    const   weblink             = 'imswl_xmlv1p0';

    public static $checker = array(self::webcontent,
                                   self::assessment,
                                   self::associatedcontent,
                                   self::discussiontopic,
                                   self::questionbank,
                                   self::weblink);

    /**
    * Validate if the type are valid or not
    *
    * @param string $type
    * @return bool
    */
    public function valid($type)
    {
        return in_array($type, self::$checker);
    }

    public function __construct()
    {
        $this->ccnamespaces     = array('imscc'    => 'http://www.imsglobal.org/xsd/imscc/imscp_v1p1',
                                        'lomimscc' => 'http://ltsc.ieee.org/xsd/imscc/LOM',
                                        'lom'      => 'http://ltsc.ieee.org/xsd/LOM',
                                        'voc'      => 'http://ltsc.ieee.org/xsd/LOM/vocab',
                                        'xsi'      => 'http://www.w3.org/2001/XMLSchema-instance'
                                        );

        $this->ccnsnames = array(
            'imscc'    => 'http://www.imsglobal.org/profile/cc/ccv1p0/derived_schema/imscp_v1p2_localised.xsd',
            'lom'      => 'http://www.imsglobal.org/profile/cc/ccv1p0/derived_schema/domainProfile_2/lomLoose_localised.xsd',
            'lomimscc' => 'http://www.imsglobal.org/profile/cc/ccv1p0/derived_schema/domainProfile_1/lomLoose_localised.xsd',
            'voc'      => 'http://www.imsglobal.org/profile/cc/ccv1p0/derived_schema/domainProfile_2/vocab/loose.xsd'
        );

        $this->ccversion        = '1.0.0';
        $this->camversion       = '1.0.0';
        $this->_generator       = 'Common Cartridge generator';
    }

    protected function on_create(DOMDocument &$doc, $rootmanifestnode = null, $nmanifestID = null)
    {
        $doc->formatOutput       = true;
        $doc->preserveWhiteSpace = true;


        $this->manifestID = is_null($nmanifestID) ? CcHelpers::uuidgen('M_') : $nmanifestID;
        $mUUID            = $doc->createAttribute('identifier');
        $mUUID->nodeValue = $this->manifestID;


        if (is_null($rootmanifestnode)) {
            if (!empty($this->_generator)) {
                $comment = $doc->createComment($this->_generator);
                $doc->appendChild($comment);
            }

            $rootel = $doc->createElementNS($this->ccnamespaces['imscc'], 'manifest');
            $rootel->appendChild($mUUID);
            $doc->appendChild($rootel);

            // Add all namespaces.
            foreach ($this->ccnamespaces as $key => $value) {
                $dummy_attr = $key.":dummy";
                $doc->createAttributeNS($value, $dummy_attr);
            }

            // Add location of schemas.
            $schemaLocation = '';
            foreach ($this->ccnsnames as $key => $value) {
                $vt = empty($schemaLocation) ? '' : ' ';
                $schemaLocation .= $vt.$this->ccnamespaces[$key].' '.$value;
            }
            $aSchemaLoc            = $doc->createAttributeNS($this->ccnamespaces['xsi'], 'xsi:schemaLocation');
            $aSchemaLoc->nodeValue = $schemaLocation;
            $rootel->appendChild($aSchemaLoc);

        } else {
            $rootel = $doc->createElementNS($this->ccnamespaces['imscc'], 'imscc:manifest');
            $rootel->appendChild($mUUID);
        }

        $metadata      = $doc->createElementNS($this->ccnamespaces['imscc'], 'metadata');
        $schema        = $doc->createElementNS($this->ccnamespaces['imscc'], 'schema', 'IMS Common Cartridge');
        $schemaversion = $doc->createElementNS($this->ccnamespaces['imscc'], 'schemaversion', $this->ccversion);

        $metadata->appendChild($schema);
        $metadata->appendChild($schemaversion);
        $rootel->appendChild($metadata);

        if (!is_null($rootmanifestnode)) {
            $rootmanifestnode->appendChild($rootel);
        }

        $organizations = $doc->createElementNS($this->ccnamespaces['imscc'], 'organizations');
        $rootel->appendChild($organizations);
        $resources = $doc->createElementNS($this->ccnamespaces['imscc'], 'resources');
        $rootel->appendChild($resources);

        return true;
    }

    protected function update_attribute(DOMDocument &$doc, $attrname, $attrvalue, DOMElement &$node)
    {
        $busenew = (is_object($node) && $node->hasAttribute($attrname));
        $nResult = null;
        if (!$busenew && is_null($attrvalue)) {
            $node->removeAttribute($attrname);
        } else {
            $nResult = $busenew ? $node->getAttributeNode($attrname) : $doc->createAttribute($attrname);
            $nResult->nodeValue = $attrvalue;
            if (!$busenew) {
                $node->appendChild($nResult);
            }
        }
        return $nResult;
    }

    protected function update_attribute_ns(DOMDocument &$doc, $attrname, $attrnamespace,$attrvalue, DOMElement &$node)
    {
        $busenew = (is_object($node) && $node->hasAttributeNS($attrnamespace, $attrname));
        $nResult = null;
        if (!$busenew && is_null($attrvalue)) {
            $node->removeAttributeNS($attrnamespace, $attrname);
        } else {
            $nResult = $busenew ? $node->getAttributeNodeNS($attrnamespace, $attrname) :
                $doc->createAttributeNS($attrnamespace, $attrname);
            $nResult->nodeValue = $attrvalue;
            if (!$busenew) {
                $node->appendChild($nResult);
            }
        }
        return $nResult;
    }

    protected function get_child_node(DOMDocument &$doc, $itemname, DOMElement &$node)
    {
        $nlist = $node->getElementsByTagName($itemname);
        $item = is_object($nlist) && ($nlist->length > 0) ? $nlist->item(0) : null;
        return $item;
    }

    protected function update_child_item(DOMDocument &$doc, $itemname, $itemvalue, DOMElement &$node, $attrtostore=null)
    {
        $tnode = $this->get_child_node($doc, 'title', $node);
        $usenew = is_null($tnode);
        $tnode = $usenew ? $doc->createElementNS($this->ccnamespaces['imscc'], $itemname) : $tnode;
        if (!is_null($attrtostore)) {
            foreach ($attrtostore as $key => $value) {
                $this->update_attribute($doc, $key, $value, $tnode);
            }
        }
        $tnode->nodeValue = $itemvalue;
        if ($usenew) {
            $node->appendChild($tnode);
        }
    }

    protected function update_items($items, DOMDocument &$doc, DOMElement &$xmlnode)
    {
        foreach ($items as $key => $item) {
            $itemnode = $doc->createElementNS($this->ccnamespaces['imscc'], 'item');
            $this->update_attribute($doc, 'identifier'   , $key                , $itemnode);
            $this->update_attribute($doc, 'identifierref', $item->identifierref, $itemnode);
            $this->update_attribute($doc, 'parameters'   , $item->parameters   , $itemnode);
            if (!empty($item->title)) {
                $titlenode = $doc->createElementNS($this->ccnamespaces['imscc'],
                                                   'title',
                                                   $item->title);
                $itemnode->appendChild($titlenode);
            }
            if ($item->has_child_items()) {
                $this->update_items($item->childitems, $doc, $itemnode);
            }
            $xmlnode->appendChild($itemnode);
        }
    }

    /**
     * Create a Resource (How to)
     *
     * @param CcIResource $res
     * @param DOMDocument $doc
     * @param object $xmlnode
     * @return DOMNode
     */
    protected function create_resource(CcIResource &$res, DOMDocument &$doc, $xmlnode=null)
    {
        $usenew = is_object($xmlnode);
        $dnode  = $usenew ? $xmlnode : $doc->createElementNS($this->ccnamespaces['imscc'], "resource");
        $this->update_attribute($doc, 'identifier', $res->identifier, $dnode);
        $this->update_attribute($doc, 'type', $res->type, $dnode);
        !is_null($res->href) ? $this->update_attribute($doc, 'href', $res->href, $dnode) : null;
        $this->update_attribute($doc, 'base', $res->base, $dnode);

        foreach ($res->files as $file) {
            $nd = $doc->createElementNS($this->ccnamespaces['imscc'], 'file');
            $ndatt = $doc->createAttribute('href');
            $ndatt->nodeValue = $file;
            $nd->appendChild($ndatt);
            $dnode->appendChild($nd);
        }
        $this->resources[$res->identifier]   = $res;
        $this->resources_ind[$res->files[0]] = $res->identifier;

        foreach ($res->dependency as $dependency) {
            $nd = $doc->createElementNS($this->ccnamespaces['imscc'], 'dependency');
            $ndatt = $doc->createAttribute('identifierref');
            $ndatt->nodeValue = $dependency;
            $nd->appendChild($ndatt);
            $dnode->appendChild($nd);
        }

        return $dnode;
    }

    /**
     * Create an Item Folder (How To)
     *
     * @param CcIOrganization $org
     * @param DOMDocument $doc
     * @param DOMElement $xmlnode
     */
    protected function create_item_folder(CcIOrganization &$org, DOMDocument &$doc, DOMElement &$xmlnode = null)
    {

        $itemfoldernode = $doc->createElementNS($this->ccnamespaces['imscc'], 'item');
        $this->update_attribute($doc, 'identifier', "root", $itemfoldernode);

        if ($org->has_items()) {
            $this->update_items($org->itemlist, $doc, $itemfoldernode);
        }
        if (is_null($this->organizations)) {
            $this->organizations = array();
        }
        $this->organizations[$org->identifier] = $org;

        $xmlnode->appendChild($itemfoldernode);
    }

    /**
     * Create an Organization (How To)
     *
     * @param CcIOrganization $org
     * @param DOMDocument $doc
     * @param object $xmlnode
     * @return DOMNode
     */
    protected function create_organization(CcIOrganization &$org, DOMDocument &$doc, $xmlnode = null)
    {

        $usenew = is_object($xmlnode);
        $dnode  = $usenew ? $xmlnode : $doc->createElementNS($this->ccnamespaces['imscc'], "organization");
        $this->update_attribute($doc, 'identifier', $org->identifier, $dnode);
        $this->update_attribute($doc, 'structure', $org->structure, $dnode);

        $this->create_item_folder($org, $doc, $dnode);

        return $dnode;
    }

    /**
     * Create Metadata For Manifest (How To)
     *
     * @param CcIMetadataManifest $met
     * @param DOMDocument $doc
     * @param object $xmlnode
     * @return DOMNode
     */
    protected function create_metadata_manifest(CcIMetadataManifest $met, DOMDocument &$doc, $xmlnode = null)
    {

        $dnode = $doc->createElementNS($this->ccnamespaces['lomimscc'], "lom");
        if (!empty($xmlnode)) {
            $xmlnode->appendChild($dnode);
        }
        $dnodegeneral   = empty($met->arraygeneral) ? null : $this->create_metadata_general($met, $doc, $xmlnode);
        $dnodetechnical = empty($met->arraytech) ? null : $this->create_metadata_technical($met, $doc, $xmlnode);
        $dnoderights    = empty($met->arrayrights) ? null : $this->create_metadata_rights($met, $doc, $xmlnode);
        $dnodelifecycle = empty($met->arraylifecycle) ? null : $this->create_metadata_lifecycle($met, $doc, $xmlnode);

        !is_null($dnodegeneral) ? $dnode->appendChild($dnodegeneral) : null;
        !is_null($dnodetechnical) ? $dnode->appendChild($dnodetechnical) : null;
        !is_null($dnoderights) ? $dnode->appendChild($dnoderights) : null;
        !is_null($dnodelifecycle) ? $dnode->appendChild($dnodelifecycle) : null;

        return $dnode;

    }

    /**
     * Create Metadata For Resource (How To)
     *
     * @param CcIMetadataResource $met
     * @param DOMDocument $doc
     * @param object $xmlnode
     * @return DOMNode
     */
    protected function create_metadata_resource(CcIMetadataResource $met, DOMDocument &$doc, $xmlnode = null)
    {

        $dnode = $doc->createElementNS($this->ccnamespaces['lom'], "lom");

        !empty($xmlnode) ? $xmlnode->appendChild($dnode) : null;
        !empty($met->arrayeducational) ? $this->create_metadata_educational($met, $doc, $dnode) : null;

        return $dnode;
    }

    /**
     * Create Metadata For File (How To)
     *
     * @param CcIMetadataFile $met
     * @param DOMDocument $doc
     * @param Object $xmlnode
     * @return DOMNode
     */
    protected function create_metadata_file(CcIMetadataFile $met, DOMDocument &$doc, $xmlnode = null)
    {

        $dnode = $doc->createElementNS($this->ccnamespaces['lom'], "lom");

        !empty($xmlnode) ? $xmlnode->appendChild($dnode) : null;
        !empty($met->arrayeducational) ? $this->create_metadata_educational($met, $doc, $dnode) : null;

        return $dnode;
    }

    /**
     * Create General Metadata (How To)
     *
     * @param object $met
     * @param DOMDocument $doc
     * @param object $xmlnode
     * @return DOMNode
     */
    protected function create_metadata_general($met, DOMDocument &$doc, $xmlnode)
    {
        $nd = $doc->createElementNS($this->ccnamespaces['lomimscc'], 'general');

        foreach ($met->arraygeneral as $name => $value) {
            !is_array($value) ? $value = array($value) : null;
            foreach ($value as $v) {
                if ($name != 'language' && $name != 'catalog' && $name != 'entry') {
                    $nd2 = $doc->createElementNS($this->ccnamespaces['lomimscc'], $name);
                    $nd3 = $doc->createElementNS($this->ccnamespaces['lomimscc'], 'string', $v[1]);
                    $ndatt = $doc->createAttribute('language');
                    $ndatt->nodeValue = $v[0];
                    $nd3->appendChild($ndatt);
                    $nd2->appendChild($nd3);
                    $nd->appendChild($nd2);
                } else {
                    if ($name == 'language') {
                        $nd2 = $doc->createElementNS($this->ccnamespaces['lomimscc'], $name, $v[0]);
                        $nd->appendChild($nd2);
                    }
                }
            }
        }
        if (!empty($met->arraygeneral['catalog']) || !empty($met->arraygeneral['entry'])) {
            $nd2 = $doc->createElementNS($this->ccnamespaces['lomimscc'], 'identifier');
            $nd->appendChild($nd2);
            if (!empty($met->arraygeneral['catalog'])) {
                $nd3 = $doc->createElementNS($this->ccnamespaces['lomimscc'], 'catalog', $met->arraygeneral['catalog'][0][0]);
                $nd2->appendChild($nd3);
            }
            if (!empty($met->arraygeneral['entry'])) {
                $nd4 = $doc->createElementNS($this->ccnamespaces['lomimscc'], 'entry', $met->arraygeneral['entry'][0][0]);
                $nd2->appendChild($nd4);
            }
        }
        return $nd;
    }

    /**
     * Create Technical Metadata (How To)
     *
     * @param object $met
     * @param DOMDocument $doc
     * @param object $xmlnode
     * @return DOMNode
     */
    protected function create_metadata_technical($met, DOMDocument &$doc, $xmlnode)
    {
        $nd = $doc->createElementNS($this->ccnamespaces['lomimscc'], 'technical');
        $xmlnode->appendChild($nd);

        foreach ($met->arraytech as $name => $value) {
            !is_array($value) ? $value = array($value) : null;
            foreach ($value as $v) {
                $nd2 = $doc->createElementNS($this->ccnamespaces['lomimscc'], $name, $v[0]);
                $nd->appendChild($nd2);
            }
        }
        return $nd;
    }


    /**
     * Create Rights Metadata (How To)
     *
     * @param object $met
     * @param DOMDocument $doc
     * @param object $xmlnode
     * @return DOMNode
     */
    protected function create_metadata_rights($met, DOMDocument &$doc, $xmlnode)
    {

        $nd = $doc->createElementNS($this->ccnamespaces['lomimscc'], 'rights');

        foreach ($met->arrayrights as $name => $value) {
            !is_array($value) ? $value = array($value) : null;
            foreach ($value as $v) {
                if ($name == 'description') {
                    $nd2 = $doc->createElementNS($this->ccnamespaces['lomimscc'], $name);
                    $nd3 = $doc->createElementNS($this->ccnamespaces['lomimscc'], 'string', $v[1]);
                    $ndatt = $doc->createAttribute('language');
                    $ndatt->nodeValue = $v[0];
                    $nd3->appendChild($ndatt);
                    $nd2->appendChild($nd3);
                    $nd->appendChild($nd2);
                } else if ($name == 'copyrightAndOtherRestrictions' || $name == 'cost') {
                    $nd2 = $doc->createElementNS($this->ccnamespaces['lomimscc'], $name);
                    $nd3 = $doc->createElementNS($this->ccnamespaces['lomimscc'], 'value', $v[0]);
                    $nd2->appendChild($nd3);
                    $nd->appendChild($nd2);
                }
            }
        }
        return $nd;
    }

    /**
     * Create Lifecycle Metadata (How To)
     *
     * @param object $met
     * @param DOMDocument $doc
     * @param object $xmlnode
     * @return DOMNode
     */
    protected function create_metadata_lifecycle($met, DOMDocument &$doc, $xmlnode)
    {

        $nd  = $doc->createElementNS($this->ccnamespaces['lomimscc'], 'lifeCycle');
        $nd2 = $doc->createElementNS($this->ccnamespaces['lomimscc'], 'contribute');

        $nd->appendChild($nd2);
        $xmlnode->appendChild($nd);

        foreach ($met->arraylifecycle as $name => $value) {
            !is_array($value) ? $value = array($value) : null;
            foreach ($value as $v) {
                if ($name == 'role') {
                    $nd3 = $doc->createElementNS($this->ccnamespaces['lomimscc'], $name);
                    $nd2->appendChild($nd3);
                    $nd4 = $doc->createElementNS($this->ccnamespaces['lomimscc'], 'value', $v[0]);
                    $nd3->appendChild($nd4);
                } else {
                    if ($name == 'date') {
                        $nd3 = $doc->createElementNS($this->ccnamespaces['lomimscc'], $name);
                        $nd2->appendChild($nd3);
                        $nd4 = $doc->createElementNS($this->ccnamespaces['lomimscc'], 'dateTime', $v[0]);
                        $nd3->appendChild($nd4);
                    } else {
                        $nd3 = $doc->createElementNS($this->ccnamespaces['lomimscc'], $name, $v[0]);
                        $nd2->appendChild($nd3);
                    }
                }
            }
        }
        return $nd;
    }

    /**
     * Create Education Metadata (How To)
     *
     * @param object $met
     * @param DOMDocument $doc
     * @param object $xmlnode
     * @return DOMNode
     */
    public function create_metadata_educational($met, DOMDocument  &$doc, $xmlnode)
    {
        $nd  = $doc->createElementNS($this->ccnamespaces['lom'], 'educational');
        $nd2 = $doc->createElementNS($this->ccnamespaces['lom'], 'intendedEndUserRole');
        $nd3 = $doc->createElementNS($this->ccnamespaces['voc'], 'vocabulary');

        $xmlnode->appendChild($nd);
        $nd->appendChild($nd2);
        $nd2->appendChild($nd3);

        foreach ($met->arrayeducational as $name => $value) {
            !is_array($value) ? $value = array($value) : null;
            foreach ($value as $v) {
                $nd4 = $doc->createElementNS($this->ccnamespaces['voc'], $name, $v[0]);
                $nd3->appendChild($nd4);
            }
        }
        return $nd;
    }
}
