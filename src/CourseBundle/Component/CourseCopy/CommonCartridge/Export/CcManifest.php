<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_manifest.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIManifest;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIMetadataFile;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIMetadataManifest;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIMetadataResource;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIOrganization;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIResource;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Utils\CcHelpers;
use DOMNode;
use Exception;

/**
 * Common Cartridge Manifest builder.
 * Relies on helper methods provided by XMLGenericDocument (registerNS, node, nodeList, appendNewElementNs, etc.).
 */
class CcManifest extends XMLGenericDocument implements CcIManifest
{
    /**
     * @var int|null
     */
    private $ccversion;

    /**
     * @var CcVersion13|null
     */
    private $ccobj;

    /**
     * @var CcVersion13|null
     */
    private $rootmanifest;

    /**
     * @var CcVersion13|null
     */
    private $activemanifest;

    /**
     * @var CcVersion13|null
     */
    private $parentmanifest;

    /**
     * @var CcVersion13|null
     */
    private $parentparentmanifest;

    /**
     * @var array
     */
    private $ares = [];

    /**
     * @var string|null
     */
    private $mainidentifier;

    /**
     * @param int|mixed        $ccver
     * @param CcVersion13|null $activemanifest
     * @param CcVersion13|null $parentmanifest
     * @param CcVersion13|null $parentparentmanifest
     */
    public function __construct($ccver = 13, $activemanifest = null, $parentmanifest = null, $parentparentmanifest = null)
    {
        $this->ccversion = \is_int($ccver) ? $ccver : 13;
        $this->activemanifest = $activemanifest;
        $this->parentmanifest = $parentmanifest;
        $this->parentparentmanifest = $parentparentmanifest;

        $this->ccobj = new CcVersion13();

        // Initialize XML document (UTF-8, formatted)
        parent::__construct('UTF-8', true);
    }

    /**
     * Register namespaces so XPath queries can resolve prefixes.
     */
    public function registerNamespacesForXpath(): void
    {
        if (null === $this->activemanifest) {
            return;
        }
        $scnam = $this->activemanifest->getCcNamespaces();
        foreach ($scnam as $key => $value) {
            $this->registerNS($key, $value);
        }
    }

    /**
     * Add metadata at manifest level.
     */
    public function addMetadataManifest(CcIMetadataManifest $met): void
    {
        $metanode = $this->node(
            "//imscc:manifest[@identifier='".
            $this->activemanifest->manifestID().
            "']/imscc:metadata"
        );

        $nmeta = $this->activemanifest->createMetadataNode($met, $this->doc, $metanode);
        $metanode->appendChild($nmeta);
    }

    /**
     * Add metadata at resource level.
     *
     * @param string $identifier
     */
    public function addMetadataResource(CcIMetadataResource $met, $identifier): void
    {
        $metanode = $this->node("//imscc:resource[@identifier='".$identifier."']");
        $metanode2 = $this->node("//imscc:resource[@identifier='".$identifier."']/imscc:file");

        $nspaces = $this->activemanifest->getCcNamespaces();
        $dnode = $this->appendNewElementNs($metanode2, $nspaces['imscc'], 'metadata');

        $this->activemanifest->createMetadataResourceNode($met, $this->doc, $dnode);
    }

    /**
     * Add metadata for a specific file (inside a resource).
     *
     * @param string $identifier
     * @param string $filename
     */
    public function addMetadataFile(CcIMetadataFile $met, $identifier, $filename): void
    {
        if (empty($met) || empty($identifier) || empty($filename)) {
            throw new Exception('Trying to add a metadata file with null/empty values!');
        }

        $metanode = $this->node(
            "//imscc:resource[@identifier='".$identifier."']".
            "/imscc:file[@href='".$filename."']"
        );

        $nspaces = $this->activemanifest->getCcNamespaces();
        $dnode = $this->doc->createElementNS($nspaces['imscc'], 'metadata');

        $metanode->appendChild($dnode);
        $this->activemanifest->createMetadataFileNode($met, $this->doc, $dnode);
    }

    /**
     * Called when document is created from scratch.
     */
    public function onCreate()
    {
        $this->activemanifest = new CcVersion13();
        $this->rootmanifest = $this->activemanifest;

        $result = $this->activemanifest->createManifest($this->doc);
        $this->registerNamespacesForXpath();

        return $result;
    }

    /**
     * Relative base path used by the manifest (if any).
     */
    public function getRelativeBasePath()
    {
        return $this->activemanifest->base();
    }

    /**
     * Return a new manifest wrapper pointing to the parent manifest.
     * (Kept for compatibility with original structure.).
     */
    public function parentManifest(): self
    {
        return new self($this->ccversion, $this->parentmanifest, $this->parentparentmanifest, null);
    }

    /**
     * Return a new manifest wrapper pointing to the root manifest.
     * (Kept for compatibility with original structure.).
     */
    public function rootManifest(): self
    {
        return new self($this->ccversion, $this->rootmanifest, null, null);
    }

    /**
     * Current manifest identifier.
     */
    public function manifestID()
    {
        return $this->activemanifest->manifestID();
    }

    /**
     * Namespaces declared in the (root) manifest.
     *
     * @return array<string,string>
     */
    public function getManifestNamespaces(): array
    {
        return $this->rootmanifest->getCcNamespaces();
    }

    /**
     * Add a new organization node to the manifest.
     */
    public function addNewOrganization(CcIOrganization &$org): void
    {
        $norg = $this->activemanifest->createOrganizationNode($org, $this->doc);
        $orgnode = $this->node(
            "//imscc:manifest[@identifier='".
            $this->activemanifest->manifestID().
            "']/imscc:organizations"
        );
        $orgnode->appendChild($norg);
    }

    /**
     * Build a list of CcResources objects from current manifest.
     *
     * @param string $searchspecific Optional XPath suffix filter
     *
     * @return CcResources[]
     */
    public function getResources($searchspecific = ''): array
    {
        $reslist = $this->getResourceList($searchspecific);
        $resourcelist = [];
        foreach ($reslist as $resourceitem) {
            $resourcelist[] = new CcResources($this, $resourceitem);
        }

        return $resourcelist;
    }

    /**
     * Resolve namespace URL by short name.
     *
     * @param mixed $nsname
     */
    public function getCcNamespacePath($nsname)
    {
        if (\is_string($nsname) && '' !== $nsname) {
            $scnam = $this->activemanifest->getCcNamespaces();

            return $scnam[$nsname] ?? null;
        }

        return null;
    }

    /**
     * Get DOMNodeList of resources from current manifest.
     *
     * @param string $searchspecific Optional XPath suffix filter
     */
    public function getResourceList($searchspecific = '')
    {
        return $this->nodeList(
            "//imscc:manifest[@identifier='".
            $this->activemanifest->manifestID().
            "']/imscc:resources/imscc:resource".$searchspecific
        );
    }

    /**
     * Called after loading an existing manifest.
     */
    public function onLoad(): bool
    {
        $this->registerNamespacesForXpath();
        $this->fillManifest();

        return true;
    }

    /**
     * Called before saving the document (no-op).
     */
    public function onSave(): bool
    {
        return true;
    }

    /**
     * Add a resource (and optionally its dependent files) to the manifest.
     *
     * @param string|object $identifier If object, treated as DOM node to append to (dependencies path)
     * @param string        $type       CC resource type (default 'webcontent')
     *
     * @return array{0:string|null,1:string|null} [identifier, first-file]
     */
    public function addResource(CcIResource $res, $identifier = null, $type = 'webcontent'): array
    {
        if (!$this->ccobj->valid($type)) {
            throw new Exception('Invalid resource type.');
        }

        if (null === $res) {
            throw new Exception('Invalid resource instance.');
        }

        $rst = $res;

        // Dependencies path (when $identifier is a node)
        if (\is_object($identifier)) {
            $this->activemanifest->createResourceNode($rst, $this->doc, $identifier);
        } else {
            $nresnode = null;

            $rst->type = $type;
            if (!CcHelpers::isHtml($rst->filename)) {
                $rst->href = null;
            }

            $this->activemanifest->createResourceNode($rst, $this->doc, $nresnode);

            // Add dependent files as child resources if they aren't already present
            foreach ($rst->files as $file) {
                $ident = $this->getIdentifierByFilename($file);
                if (null === $ident) {
                    $newres = new CcResources($rst->manifestroot, $file);
                    if (!CcHelpers::isHtml($file)) {
                        $newres->href = null;
                    }
                    $newres->type = 'webcontent';
                    $this->activemanifest->createResourceNode($newres, $this->doc, $nresnode);
                }
            }
        }

        return [$rst->identifier ?? null, $rst->files[0] ?? null];
    }

    /**
     * Mark a resource as instructor-only (adds educational metadata later).
     *
     * @param mixed $identifier
     * @param mixed $value
     */
    public function updateInstructoronly($identifier, $value = false): void
    {
        if (isset($this->activemanifest->resources[$identifier])) {
            $resource = $this->activemanifest->resources[$identifier];
            $resource->instructoronly = (bool) $value;
        }
    }

    /**
     * Append all resource nodes into the manifest <resources> section.
     */
    public function putNodes(): DOMNode
    {
        $resnodestr = "//imscc:manifest[@identifier='".
            $this->activemanifest->manifestID().
            "']/imscc:resources";

        /** @var DOMNode $resnode */
        $resnode = $this->node($resnodestr);

        // If a file is used by multiple resources, convert into dependency
        foreach ($this->activemanifest->resources as $v) { // key is not needed
            $firstFile = $v->files[0] ?? '';
            $depen = $this->checkIfExistInOther($firstFile, $v->identifier ?? '');
            if (!empty($depen)) {
                $this->replaceFileXDependency($depen, $firstFile);
                $v->type = 'webcontent';
            }
        }

        foreach ($this->activemanifest->resources as $node) {
            $rnode = $this->activemanifest->createResourceNode($node, $this->doc, null);
            $resnode->appendChild($rnode);

            // Instructor-only: add educational metadata (intended user role = instructor)
            if (!empty($node->instructoronly)) {
                // Keep literal value to avoid dependency on a missing enum/class.
                $metafileceduc = new CcMetadataResourceEducational();
                $metafileceduc->setValue('instructor');

                $metafile = new CcMetadataResource();
                $metafile->addMetadataResourceEducational($metafileceduc);

                $this->activemanifest->createMetadataEducational($metafile, $this->doc, $rnode);
            }
        }

        return $resnode;
    }

    /**
     * Populate manifest-internal state from current DOM (left unimplemented in original code).
     */
    private function fillManifest(): void
    {
        // Intentionally left blank (original Moodle code note: TODO - critical).
        // Implement if you need to reconstruct $this->activemanifest->resources from DOM.
    }

    /**
     * Check if the given file name is present in other resources (returns identifiers).
     *
     * @param string $name
     * @param string $identifier
     *
     * @return string[]
     */
    private function checkIfExistInOther($name, $identifier): array
    {
        $status = [];
        foreach ($this->activemanifest->resources as $value) {
            if (($value->identifier !== $identifier) && isset($value->files[$name])) {
                $status[] = $value->identifier;
            }
        }

        return $status;
    }

    /**
     * Replace direct file usage with a dependency entry.
     *
     * @param string[] $depen
     */
    // Replace a duplicated file usage by a dependency reference
    private function replaceFileXDependency(array $depen, string $name): bool
    {
        foreach ($depen as $value) { // key is not needed
            $ident = $this->getIdentifierByFilename($name);

            // Remove file from the resource's file list
            $this->activemanifest->resources[$value]->files =
                $this->arrayRemoveByValue($this->activemanifest->resources[$value]->files, $name);

            // Add dependency if not present (and identifier resolved)
            if (null !== $ident
                && !\in_array($ident, $this->activemanifest->resources[$value]->dependency, true)
            ) {
                $this->activemanifest->resources[$value]->dependency[] = $ident;
            }
        }

        return true;
    }

    /**
     * Find resource identifier by file name.
     *
     * @param mixed $name
     */
    private function getIdentifierByFilename($name)
    {
        $result = null;
        if (isset($this->activemanifest->resourcesInd[$name])) {
            $result = $this->activemanifest->resourcesInd[$name];
        }

        return $result;
    }

    /**
     * Remove value from array (reindex).
     *
     * @param mixed $value
     */
    private function arrayRemoveByValue(array $arr, $value): array
    {
        return array_values(array_diff($arr, [$value]));
    }

    /**
     * Remove key from array (reindex).
     *
     * @param mixed $key
     */
    private function arrayRemoveByKey(array $arr, $key): array
    {
        return array_values(array_diff_key($arr, [$key => true]));
    }
}
