<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_version_base.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\CcOrganization;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\CcResource;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIManifest;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIMetadataFile;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIMetadataManifest;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIMetadataResource;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIOrganization;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIResource;
use DOMDocument;
use DOMElement;

/**
 * Abstract Version Base class.
 */
abstract class CcVersionBase
{
    public $resources;
    public $resourcesInd;
    public $organizations;
    public $ccversion;
    public $camversion;

    protected $_generator;
    protected $ccnamespaces = [];
    protected $isrootmanifest = false;
    protected $manifestID;
    protected $organizationid;
    protected $metadata;
    protected $base;

    public function getCcNamespaces()
    {
        return $this->ccnamespaces;
    }

    public function createManifest(DOMDocument &$doc, $rootmanifestnode = null)
    {
        return $this->onCreate($doc, $rootmanifestnode);
    }

    public function createResourceNode(CcIResource &$res, DOMDocument &$doc, $xmlnode = null)
    {
        return $this->createResource($res, $doc, $xmlnode);
    }

    public function createMetadataNode(&$met, DOMDocument &$doc, $xmlnode = null)
    {
        return $this->createMetadataManifest($met, $doc, $xmlnode);
    }

    public function createMetadataResourceNode(&$met, DOMDocument &$doc, $xmlnode = null)
    {
        return $this->createMetadataResource($met, $doc, $xmlnode);
    }

    public function createMetadataFileNode(&$met, DOMDocument &$doc, $xmlnode = null)
    {
        return $this->createMetadataFile($met, $doc, $xmlnode);
    }

    public function createOrganizationNode(CcIOrganization &$org, DOMDocument &$doc, $xmlnode = null)
    {
        return $this->createOrganization($org, $doc, $xmlnode);
    }

    public function manifestID()
    {
        return $this->manifestID;
    }

    public function setManifestID($id): void
    {
        $this->manifestID = $id;
    }

    public function getBase()
    {
        return $this->base;
    }

    public function setBase($baseval): void
    {
        $this->base = $baseval;
    }

    public function importResources(DOMElement &$node, CcIManifest &$doc): void
    {
        if (null === $this->resources) {
            $this->resources = [];
        }
        $nlist = $node->getElementsByTagNameNS($this->ccnamespaces['imscc'], 'resource');
        if (\is_object($nlist)) {
            foreach ($nlist as $nd) {
                $sc = new CcResource($doc, $nd);
                $this->resources[$sc->identifier] = $sc;
            }
        }
    }

    public function importOrganizationItems(DOMElement &$node, CcIManifest &$doc): void
    {
        if (null === $this->organizations) {
            $this->organizations = [];
        }
        $nlist = $node->getElementsByTagNameNS($this->ccnamespaces['imscc'], 'organization');
        if (\is_object($nlist)) {
            foreach ($nlist as $nd) {
                $sc = new CcOrganization($nd, $doc);
                $this->organizations[$sc->identifier] = $sc;
            }
        }
    }

    public function setGenerator($value): void
    {
        $this->_generator = $value;
    }

    abstract protected function onCreate(DOMDocument &$doc, $rootmanifestnode = null, $nmanifestID = null);

    abstract protected function createMetadataManifest(CcIMetadataManifest $met, DOMDocument &$doc, $xmlnode = null);

    abstract protected function createMetadataResource(CcIMetadataResource $met, DOMDocument &$doc, $xmlnode = null);

    abstract protected function createMetadataFile(CcIMetadataFile $met, DOMDocument &$doc, $xmlnode = null);

    abstract protected function createResource(CcIResource &$res, DOMDocument &$doc, $xmlnode = null);

    abstract protected function createOrganization(CcIOrganization &$org, DOMDocument &$doc, $xmlnode = null);
}
