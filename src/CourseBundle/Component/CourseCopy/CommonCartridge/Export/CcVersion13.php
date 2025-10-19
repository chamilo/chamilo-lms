<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\CcVersion1;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIOrganization;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIResource;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use DOMXPath;

/**
 * CC v1.3 implementation aligned with what CcManifest expects.
 * IMPORTANT: Method signatures MUST match CcVersionBase (references and optional args).
 */
class CcVersion13 extends CcVersion1
{
    public const WEBCONTENT = 'webcontent';
    public const QUESTIONBANK = 'imsqti_xmlv1p3/imscc_xmlv1p3/question-bank';
    public const ASSESSMENT = 'imsqti_xmlv1p3/imscc_xmlv1p3/assessment';
    public const ASSOCIATEDCONTENT = 'associatedcontent/imscc_xmlv1p3/learning-application-resource';
    public const DISCUSSIONTOPIC = 'imsdt_xmlv1p3';
    public const WEBLINK = 'imswl_xmlv1p3';
    public const BASICLTI = 'imsbasiclti_xmlv1p3';

    /**
     * @var string[]
     */
    public static $checker = [
        self::WEBCONTENT,
        self::ASSESSMENT,
        self::ASSOCIATEDCONTENT,
        self::DISCUSSIONTOPIC,
        self::QUESTIONBANK,
        self::WEBLINK,
        self::BASICLTI,
    ];

    private ?string $manifestId = null;

    /**
     * @var string
     */
    protected $base = '';

    public function __construct()
    {
        // CC 1.3 namespaces.
        $this->ccnamespaces = [
            'imscc' => 'http://www.imsglobal.org/xsd/imsccv1p3/imscp_v1p1',
            'lomimscc' => 'http://ltsc.ieee.org/xsd/imsccv1p3/LOM/manifest',
            'lom' => 'http://ltsc.ieee.org/xsd/imsccv1p3/LOM/resource',
            'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
            'cc' => 'http://www.imsglobal.org/xsd/imsccv1p3/imsccauth_v1p1',
        ];

        // Optional schema locations.
        $this->ccnsnames = [
            'imscc' => 'http://www.imsglobal.org/profile/cc/ccv1p3/ccv1p3_imscp_v1p2_v1p0.xsd',
            'lomimscc' => 'http://www.imsglobal.org/profile/cc/ccv1p3/LOM/ccv1p3_lommanifest_v1p0.xsd',
            'lom' => 'http://www.imsglobal.org/profile/cc/ccv1p3/LOM/ccv1p3_lomresource_v1p0.xsd',
        ];

        $this->ccversion = '1.3.0';
        $this->camversion = '1.3.0';
        $this->_generator = 'Chamilo Common Cartridge generator';

        parent::__construct();
    }

    /**
     * Validate allowed resource types.
     */
    public function valid($type)
    {
        return \in_array($type, self::$checker, true);
    }

    /**
     * Expose namespaces for XPath registration.
     */
    public function getCcNamespaces(): array
    {
        return $this->ccnamespaces;
    }

    /**
     * Manifest identifier getter (lazy).
     */
    public function manifestID(): string
    {
        if (null === $this->manifestId) {
            $this->manifestId = 'MANIFEST-'.bin2hex(random_bytes(6));
        }

        return $this->manifestId;
    }

    /**
     * Base path used by the manifest (<resource base="...">). Empty by default.
     */
    public function base(): string
    {
        return $this->base;
    }

    /**
     * MUST match: CcVersionBase->createManifest(&doc: DOMDocument, [rootmanifestnode = null])
     * Creates the root <manifest> with <organizations>, <resources>, <metadata>.
     *
     * @param mixed $rootmanifestnode optional parent to append the manifest into
     *
     * @return mixed the created manifest element (kept loose to match base)
     */
    public function createManifest(DOMDocument &$doc, $rootmanifestnode = null)
    {
        $imscc = $this->ccnamespaces['imscc'];

        $manifest = $doc->createElementNS($imscc, 'manifest');
        $manifest->setAttribute('identifier', $this->manifestID());

        // Declare xmlns:prefix for all namespaces (helps validators).
        foreach ($this->ccnamespaces as $prefix => $uri) {
            $manifest->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:'.$prefix, $uri);
        }

        // Optional xsi:schemaLocation.
        if (isset($this->ccnamespaces['xsi']) && !empty($this->ccnsnames)) {
            $schemaLocation = '';
            foreach ($this->ccnsnames as $key => $value) {
                $schemaLocation .= ('' === $schemaLocation ? '' : ' ')
                    .$this->ccnamespaces[$key].' '.$value;
            }
            if ('' !== $schemaLocation) {
                $manifest->setAttributeNS(
                    $this->ccnamespaces['xsi'],
                    'xsi:schemaLocation',
                    $schemaLocation
                );
            }
        }

        // Standard children
        $organizations = $doc->createElementNS($imscc, 'organizations');
        $resources = $doc->createElementNS($imscc, 'resources');
        $metadata = $doc->createElementNS($imscc, 'metadata');
        $manifest->appendChild($organizations);
        $manifest->appendChild($resources);
        $manifest->appendChild($metadata);

        // Append to given parent or as document element.
        if ($rootmanifestnode instanceof DOMNode) {
            $rootmanifestnode->appendChild($manifest);
        } else {
            $doc->appendChild($manifest);
        }

        return $manifest;
    }

    /**
     * Create an <organization> node. Signature is additive to base style (keeps optional $xmlnode).
     *
     * @param null|mixed $xmlnode
     */
    public function createOrganizationNode(CcIOrganization &$org, DOMDocument &$doc, $xmlnode = null)
    {
        $imscc = $this->ccnamespaces['imscc'];

        $organization = $doc->createElementNS($imscc, 'organization');
        $organization->setAttribute('identifier', 'ORG-'.bin2hex(random_bytes(4)));

        // Optional title
        if (method_exists($org, 'getTitle')) {
            $title = (string) $org->getTitle();
            if ('' !== $title) {
                $titleNode = $doc->createElementNS($imscc, 'title');
                $titleNode->appendChild(new DOMText($title));
                $organization->appendChild($titleNode);
            }
        }

        // Optional items
        if (method_exists($org, 'getItems')) {
            $items = $org->getItems();
            if (\is_array($items) && !empty($items)) {
                $this->updateItems($items, $doc, $organization);
            }
        }

        if ($xmlnode instanceof DOMNode) {
            $xmlnode->appendChild($organization);
        }

        return $organization;
    }

    /**
     * MUST match: CcVersionBase->createResourceNode(&res: CcIResource, &doc: DOMDocument, [xmlnode = null])
     * Creates a <resource> element and appends it under $xmlnode if provided, or under //imscc:resources.
     *
     * @param mixed $xmlnode optional parent (usually the <resources> container)
     *
     * @return mixed the created resource element (kept loose to match base)
     */
    public function createResourceNode(CcIResource &$res, DOMDocument &$doc, $xmlnode = null)
    {
        $imscc = $this->ccnamespaces['imscc'];

        // Find container if none provided.
        if (!$xmlnode instanceof DOMNode) {
            $xpath = new DOMXPath($doc);
            foreach ($this->ccnamespaces as $p => $u) {
                $xpath->registerNamespace($p, $u);
            }
            $xmlnode = $xpath->query('//imscc:resources')->item(0);
            if (!$xmlnode instanceof DOMNode) {
                $xmlnode = $doc->documentElement; // Fallback
            }
        }

        // Normalize fields.
        $identifier = $res->identifier ?? ('RES-'.bin2hex(random_bytes(6)));
        $type = $res->type ?? self::WEBCONTENT;
        $href = $res->href ?? null;
        $base = $res->base ?? null;

        $resourceEl = $doc->createElementNS($imscc, 'resource');
        $resourceEl->setAttribute('identifier', $identifier);
        $resourceEl->setAttribute('type', $type);
        if (!empty($href)) {
            $resourceEl->setAttribute('href', (string) $href);
        }
        if (!empty($base)) {
            $resourceEl->setAttribute('base', (string) $base);
        }

        // Files
        $files = \is_array($res->files ?? null) ? $res->files : [];
        foreach ($files as $fileHref) {
            if (null === $fileHref || '' === $fileHref) {
                continue;
            }
            $fileEl = $doc->createElementNS($imscc, 'file');
            $fileEl->setAttribute('href', (string) $fileHref);
            $resourceEl->appendChild($fileEl);

            if (property_exists($this, 'resourcesInd')) {
                $this->resourcesInd[(string) $fileHref] = $identifier;
            }
        }

        // Dependencies
        $deps = \is_array($res->dependency ?? null) ? $res->dependency : [];
        foreach ($deps as $idref) {
            if (null === $idref || '' === $idref) {
                continue;
            }
            $depEl = $doc->createElementNS($imscc, 'dependency');
            $depEl->setAttribute('identifierref', (string) $idref);
            $resourceEl->appendChild($depEl);
        }

        // Append to container.
        $xmlnode->appendChild($resourceEl);

        // Track in helpers if available and reflect identifier back into resource.
        if (property_exists($this, 'resources')) {
            $this->resources[$identifier] = $res;
        }
        $res->identifier = $identifier;

        return $resourceEl;
    }

    /**
     * === Metadata methods with signatures compatible to the base ===.
     *
     * @param mixed      $met
     * @param null|mixed $xmlnode
     */

    /**
     * Manifest-level metadata node.
     */
    public function createMetadataNode(&$met, DOMDocument &$doc, $xmlnode = null)
    {
        $imscc = $this->ccnamespaces['imscc'];
        $metaEl = $doc->createElementNS($imscc, 'metadata');

        if ($xmlnode instanceof DOMNode) {
            $xmlnode->appendChild($metaEl);
        }

        // If metadata object knows how to render itself, delegate.
        if (\is_object($met) && method_exists($met, 'generate')) {
            $met->generate($doc, $metaEl, $imscc);
        }

        return $metaEl;
    }

    /**
     * Resource-level metadata node.
     */
    public function createMetadataResourceNode(&$met, DOMDocument &$doc, $xmlnode = null)
    {
        $imscc = $this->ccnamespaces['imscc'];

        if ($xmlnode instanceof DOMNode) {
            if (\is_object($met) && method_exists($met, 'generate')) {
                $met->generate($doc, $xmlnode, $imscc);
            } else {
                $xmlnode->appendChild($doc->createElementNS($imscc, 'lom'));
            }
        }

        return $xmlnode;
    }

    /**
     * File-level metadata node.
     */
    public function createMetadataFileNode(&$met, DOMDocument &$doc, $xmlnode = null)
    {
        $imscc = $this->ccnamespaces['imscc'];

        if ($xmlnode instanceof DOMNode && \is_object($met) && method_exists($met, 'generate')) {
            $met->generate($doc, $xmlnode, $imscc);
        }

        return $xmlnode;
    }

    /**
     * Educational metadata (role etc.). Keep signature identical to parent (no strict types).
     */
    public function createMetadataEducational($met, DOMDocument &$doc, $xmlnode)
    {
        $metadata = $doc->createElementNS($this->ccnamespaces['imscc'], 'metadata');
        if ($xmlnode instanceof DOMNode) {
            $xmlnode->insertBefore($metadata, $xmlnode->firstChild);
        }
        $lom = $doc->createElementNS($this->ccnamespaces['lom'], 'lom');
        $metadata->appendChild($lom);
        $educational = $doc->createElementNS($this->ccnamespaces['lom'], 'educational');
        $lom->appendChild($educational);

        $values = isset($met->arrayeducational) ? $met->arrayeducational : [];
        foreach ($values as $value) {
            $arr = \is_array($value) ? $value : [$value];
            foreach ($arr as $v) {
                $userrole = $doc->createElementNS($this->ccnamespaces['lom'], 'intendedEndUserRole');
                $educational->appendChild($userrole);
                $nd4 = $doc->createElementNS($this->ccnamespaces['lom'], 'source', 'IMSGLC_CC_Rolesv1p2');
                $nd5 = $doc->createElementNS($this->ccnamespaces['lom'], 'value', (string) $v);
                $userrole->appendChild($nd4);
                $userrole->appendChild($nd5);
            }
        }

        return $metadata;
    }

    /**
     * Render <item> nodes under an organization (recursive).
     * Signature matches parent (types kept loose).
     *
     * @param mixed $items
     */
    protected function updateItems($items, DOMDocument &$doc, DOMElement &$xmlnode): void
    {
        foreach ($items as $key => $item) {
            $itemnode = $doc->createElementNS($this->ccnamespaces['imscc'], 'item');

            if (method_exists($this, 'updateAttribute')) {
                $this->updateAttribute($doc, 'identifier', (string) $key, $itemnode);
                $this->updateAttribute(
                    $doc,
                    'identifierref',
                    isset($item->identifierref) ? (string) $item->identifierref : null,
                    $itemnode
                );
            } else {
                $itemnode->setAttribute('identifier', (string) $key);
                if (!empty($item->identifierref)) {
                    $itemnode->setAttribute('identifierref', (string) $item->identifierref);
                }
            }

            if (isset($item->title) && null !== $item->title) {
                $titlenode = $doc->createElementNS($this->ccnamespaces['imscc'], 'title');
                $titlenode->appendChild(new DOMText((string) $item->title));
                $itemnode->appendChild($titlenode);
            }

            if (method_exists($item, 'hasChildItems') && $item->hasChildItems() && isset($item->childitems)) {
                $this->updateItems((array) $item->childitems, $doc, $itemnode);
            }

            $xmlnode->appendChild($itemnode);
        }
    }
}
