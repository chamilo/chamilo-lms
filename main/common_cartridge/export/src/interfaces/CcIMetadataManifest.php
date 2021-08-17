<?php
/* For licensing terms, see /license.txt */

/**
 * CC Metadata Manifest Interface
 */
interface CcIMetadataManifest
{
    public function add_metadata_general($obj);
    public function add_metadata_technical($obj);
    public function add_metadata_rights($obj);
    public function add_metadata_lifecycle($obj);
}

