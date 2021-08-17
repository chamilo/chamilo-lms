<?php
/* For licensing terms, see /license.txt */

/**
 * CC Manifest Interface
 */
interface CcIManifest
{
    public function on_create ();
    public function on_load ();
    public function on_save ();
    public function add_new_organization (CcIOrganization &$org);
    public function get_resources ();
    public function get_resource_list ();
    public function add_resource (CcIResource $res, $identifier=null, $type='webcontent');
    public function add_metadata_manifest(CcIMetadataManifest $met);
    public function add_metadata_resource (CcIMetadataResource $met,$identifier);
    public function add_metadata_file (CcIMetadataFile $met,$identifier,$filename);
    public function put_nodes ();
}

