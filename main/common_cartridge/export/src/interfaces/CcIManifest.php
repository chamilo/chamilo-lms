<?php
/* For licensing terms, see /license.txt */

/**
 * CC Manifest Interface.
 */
interface CcIManifest
{
    public function onCreate();

    public function onLoad();

    public function onSave();

    public function addNewOrganization(CcIOrganization &$org);

    public function getResources();

    public function getResourceList();

    public function addResource(CcIResource $res, $identifier = null, $type = 'webcontent');

    public function addMetadataManifest(CcIMetadataManifest $met);

    public function addMetadataResource(CcIMetadataResource $met, $identifier);

    public function addMetadataFile(CcIMetadataFile $met, $identifier, $filename);

    public function putNodes();
}
