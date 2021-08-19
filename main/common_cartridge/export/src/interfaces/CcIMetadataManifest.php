<?php
/* For licensing terms, see /license.txt */

/**
 * CC Metadata Manifest Interface.
 */
interface CcIMetadataManifest
{
    public function addMetadataGeneral($obj);

    public function addMetadataTechnical($obj);

    public function addMetadataRights($obj);

    public function addMetadataLifecycle($obj);
}
