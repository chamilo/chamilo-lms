<?php
/* For licensing terms, see /license.txt */

class CcVersion13 extends CcVersion1
{
    public const WEBCONTENT = 'webcontent';
    public const QUESTIONBANK = 'imsqti_xmlv1p3/imscc_xmlv1p3/question-bank';
    public const ASSESSMENT = 'imsqti_xmlv1p3/imscc_xmlv1p3/assessment';
    public const ASSOCIATEDCONTENT = 'associatedcontent/imscc_xmlv1p3/learning-application-resource';
    public const DISCUSSIONTOPIC = 'imsdt_xmlv1p3';
    public const WEBLINK = 'imswl_xmlv1p3';
    public const BASICLTI = 'imsbasiclti_xmlv1p3';

    public static $checker = [self::WEBCONTENT,
                                   self::ASSESSMENT,
                                   self::ASSOCIATEDCONTENT,
                                   self::DISCUSSIONTOPIC,
                                   self::QUESTIONBANK,
                                   self::WEBLINK,
                                   self::BASICLTI, ];

    public function __construct()
    {
        $this->ccnamespaces = ['imscc' => 'http://www.imsglobal.org/xsd/imsccv1p3/imscp_v1p1',
                                    'lomimscc' => 'http://ltsc.ieee.org/xsd/imsccv1p3/LOM/manifest',
                                    'lom' => 'http://ltsc.ieee.org/xsd/imsccv1p3/LOM/resource',
                                    'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                                    'cc' => 'http://www.imsglobal.org/xsd/imsccv1p3/imsccauth_v1p1',
                                   ];

        $this->ccnsnames = ['imscc' => 'http://www.imsglobal.org/profile/cc/ccv1p3/ccv1p3_imscp_v1p2_v1p0.xsd',
                                    'lomimscc' => 'http://www.imsglobal.org/profile/cc/ccv1p3/LOM/ccv1p3_lommanifest_v1p0.xsd',
                                    'lom' => 'http://www.imsglobal.org/profile/cc/ccv1p3/LOM/ccv1p3_lomresource_v1p0.xsd',
                                ];

        $this->ccversion = '1.3.0';
        $this->camversion = '1.3.0';
        $this->_generator = 'Chamilo Common Cartridge generator';
    }

    /**
     * Validate if the type are valid or not.
     *
     * @param string $type
     *
     * @return bool
     */
    public function valid($type)
    {
        return in_array($type, self::$checker);
    }

    /**
     * Create Education Metadata (How To).
     *
     * @param object $met
     * @param object $xmlnode
     *
     * @return DOMNode
     */
    public function createMetadataEducational($met, DOMDocument &$doc, $xmlnode)
    {
        $metadata = $doc->createElementNS($this->ccnamespaces['imscc'], 'metadata');
        $xmlnode->insertBefore($metadata, $xmlnode->firstChild);
        $lom = $doc->createElementNS($this->ccnamespaces['lom'], 'lom');
        $metadata->appendChild($lom);
        $educational = $doc->createElementNS($this->ccnamespaces['lom'], 'educational');
        $lom->appendChild($educational);

        foreach ($met->arrayeducational as $value) {
            !is_array($value) ? $value = [$value] : null;
            foreach ($value as $v) {
                $userrole = $doc->createElementNS($this->ccnamespaces['lom'], 'intendedEndUserRole');
                $educational->appendChild($userrole);
                $nd4 = $doc->createElementNS($this->ccnamespaces['lom'], 'source', 'IMSGLC_CC_Rolesv1p2');
                $nd5 = $doc->createElementNS($this->ccnamespaces['lom'], 'value', $v[0]);
                $userrole->appendChild($nd4);
                $userrole->appendChild($nd5);
            }
        }

        return $metadata;
    }

    protected function updateItems($items, DOMDocument &$doc, DOMElement &$xmlnode)
    {
        foreach ($items as $key => $item) {
            $itemnode = $doc->createElementNS($this->ccnamespaces['imscc'], 'item');
            $this->updateAttribute($doc, 'identifier', $key, $itemnode);
            $this->updateAttribute($doc, 'identifierref', $item->identifierref, $itemnode);
            if (!is_null($item->title)) {
                $titlenode = $doc->createElementNS($this->ccnamespaces['imscc'], 'title');
                $titlenode->appendChild(new DOMText($item->title));
                $itemnode->appendChild($titlenode);
            }
            if ($item->hasChildItems()) {
                $this->updateItems($item->childitems, $doc, $itemnode);
            }
            $xmlnode->appendChild($itemnode);
        }
    }
}
