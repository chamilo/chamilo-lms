<?php
$strings['plugin_title'] = "Clean deleted files";
$strings['plugin_comment'] = "Safely lists and removes orphan physical files from Chamilo 2 local resource and asset storage. Enable it in the menu_administrator region then access it from the main admin page.";
$strings['FileList'] = "Clean deleted files";
$strings['SizeTotalAllDir'] = "Total size";
$strings['NoFilesDeleted'] = "There are no files marked as deleted";
$strings['FilesDeletedMark'] = "Files marked as deleted";
$strings['FileDirSize'] = "Files size";
$strings['ConfirmDelete'] = "Are you sure you want to permanently delete this physical file?";
$strings['ErrorDeleteFile'] = "An error occurred while deleting the file";
$strings['ErrorEmptyPath'] = "There was a problem deleting the file, the path cannot be empty";
$strings['DeleteSelectedFiles'] = "Delete selected files";
$strings['ConfirmDeleteFiles'] = "Are you sure you want to permanently delete all selected physical files?";
$strings['DeletedSuccess'] = "The file deletion was successful";
$strings['path_dir'] = "Directory";
$strings['size'] = "Size";
$strings['ScanSummary'] = "Scan summary";
$strings['Chamilo2StorageNotice'] = "Uploaded files are tracked through resource_file and asset metadata. This plugin does not delete documents, resources, assets or database rows; it only lists physical files that are no longer referenced.";
$strings['SafeDryRunNotice'] = "The scan is intentionally conservative. Resource and asset files are listed only when they exist on disk but are missing from their metadata table.";
$strings['RelativePath'] = "Relative path";
$strings['StorageType'] = "Storage type";
$strings['Status'] = "Status";
$strings['CanBeDeleted'] = "Can be deleted";
$strings['ProtectedReferenced'] = "Protected / referenced";
$strings['ReferencedFileWarning'] = "If a file is still referenced by resource_file or asset, it is not listed here. Delete documents/files through the normal Chamilo 2 interfaces so Symfony, Doctrine and Vich/Flysystem can clean metadata and storage consistently.";
$strings['DeleteUnavailableReferenced'] = "This file is still referenced by Chamilo metadata and cannot be deleted here.";
$strings['DeleteSingle'] = "Delete this physical file";
$strings['ErrorInvalidToken'] = "The security token is invalid. Reload the page and try again.";
$strings['ErrorInvalidPath'] = "The file path is invalid or outside the allowed storage directories.";
$strings['ErrorMissingDeletedMarker'] = "The file is not marked with the DELETED marker.";
$strings['ErrorReferencedPath'] = "The file is still referenced by Chamilo metadata and cannot be deleted here.";
$strings['ErrorNotCleanablePath'] = "The file is not a cleanable orphan or legacy deleted file.";
$strings['DeletedFilesCount'] = "Deleted files";
$strings['SkippedFilesCount'] = "Skipped files";
$strings['NoSelection'] = "No files were selected.";
$strings['CleanableFiles'] = "Cleanable physical files";
$strings['NoCleanableFiles'] = "There are no cleanable physical files in this storage root";
$strings['Chamilo2ResourceStorage'] = "Resource files";
$strings['Chamilo2ResourceStorageHelp'] = "Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.";
$strings['Chamilo2AssetStorage'] = "Asset files";
$strings['Chamilo2AssetStorageHelp'] = "Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.";
$strings['LegacyCourseFiles'] = "Legacy course files";
$strings['LegacyUploadFiles'] = "Legacy upload files";
$strings['LegacyPublicCourseFiles'] = "Legacy public course files";
$strings['LegacyPublicUploadFiles'] = "Legacy public upload files";
$strings['LegacyDeletedStorageHelp'] = "Legacy roots are scanned only for files whose basename contains the DELETED marker.";
$strings['OrphanResourceFile'] = "Orphan resource file";
$strings['OrphanAssetFile'] = "Orphan asset file";
$strings['LegacyDeletedFile'] = "Legacy DELETED file";
$strings['OrphanResourceFiles'] = "Orphan resource files";
$strings['OrphanAssetFiles'] = "Orphan asset files";
$strings['LegacyDeletedFiles'] = "Legacy DELETED files";
$strings['Reason'] = "Reason";
$strings['ReasonOrphanResource'] = "Physical file exists in resource storage but no resource_file row points to it.";
$strings['ReasonOrphanAsset'] = "Physical file exists in asset storage but no asset row points to it.";
$strings['ReasonLegacyDeletedMarker'] = "Legacy file basename contains the DELETED marker.";

$strings['StorageNoticeShort'] = "Uploaded files are tracked through resource_file and asset metadata. This plugin only lists physical files under var/upload/resource and var/upload/assets that are no longer referenced.";
$strings['SafeNoticeShort'] = "A file with a valid database reference is protected. Documents and files should still be deleted through their normal tools.";
$strings['CheckedLocations'] = "Checked locations";
$strings['DetectionRule'] = "Detection rule";
$strings['NoCleanableFilesFound'] = "No cleanable physical files found";
$strings['NoCleanableFilesFoundHelp'] = "This is the expected result when the storage is consistent. The checked locations are shown below for transparency.";

$strings['ResourceFiles'] = "Resource files";

$strings['ResourceStorageHelp'] = "Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.";

$strings['AssetFiles'] = "Asset files";

$strings['AssetStorageHelp'] = "Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.";

$strings['RunLimitedScan'] = "Run limited scan";
$strings['ScanNotRun'] = "Scan not started";
$strings['ScanNotRunHelp'] = "The page does not scan the storage automatically because large var/upload folders can be slow. Click Run limited scan to inspect local orphan files.";
$strings['ScanLimitedWarning'] = "The scan was stopped early to keep the page responsive. Run it again later or inspect the storage from the command line if needed.";

$strings['PathFilter'] = "Path filter";
$strings['PathFilterHelp'] = "Optional. Use this to test or review a specific folder, for example clean-deleted-files-test. It matches relative paths only.";
$strings['ActivePathFilter'] = "Active path filter: %s";
