<?php
$strings['plugin_title'] = 'Verwijderde bestanden opruimen';
$strings['plugin_comment'] = 'Permanent verwijderen van bestanden gemarkeerd als verwijderd. Activeer het in het menu_administrator-gebied en accesseer het vanaf de hoofdbeheerpagin';
$strings['FileList'] = 'Lijst van bestanden gemarkeerd als verwijderd';
$strings['SizeTotalAllDir'] = 'Totale grootte (alle mappen)';
$strings['NoFilesDeleted'] = 'Er zijn geen bestanden gemarkeerd als verwijderd';
$strings['FilesDeletedMark'] = 'Bestanden gemarkeerd als verwijderd';
$strings['FileDirSize'] = 'Mapbestandsgrootte';
$strings['ConfirmDelete'] = 'Weet u zeker dat u het bestand wilt verwijderen?';
$strings['ErrorDeleteFile'] = 'Er is een fout opgetreden bij het verwijderen van het bestand';
$strings['ErrorEmptyPath'] = 'Er was een probleem bij het verwijderen van het bestand, het pad mag niet leeg zijn';
$strings['DeleteSelectedFiles'] = 'Geselecteerde bestanden verwijderen';
$strings['ConfirmDeleteFiles'] = 'Weet u zeker dat u alle geselecteerde bestanden wilt verwijderen?';
$strings['DeletedSuccess'] = 'Het verwijderen van het bestand is succesvol';
$strings['path_dir'] = 'Map';
$strings['size'] = 'Grootte';
$strings['ScanSummary'] = 'Scan summary';
$strings['Chamilo2StorageNotice'] = 'Chamilo 2 stores files through resources and assets. This plugin only lists files explicitly marked with DELETED.';
$strings['SafeDryRunNotice'] = 'This screen is a safe review step: referenced files are protected and cannot be deleted from here.';
$strings['RelativePath'] = 'Relative path';
$strings['StorageType'] = 'Storage type';
$strings['Status'] = 'Status';
$strings['CanBeDeleted'] = 'Can be deleted';
$strings['ProtectedReferenced'] = 'Protected / referenced';
$strings['ReferencedFileWarning'] = 'Files still referenced by resource_file or asset are protected. Do not delete them manually.';
$strings['DeleteUnavailableReferenced'] = 'This file is still referenced by Chamilo metadata and cannot be deleted here.';
$strings['DeleteSingle'] = 'Delete this file';
$strings['ErrorInvalidToken'] = 'The security token is invalid. Reload the page and try again.';
$strings['ErrorInvalidPath'] = 'The file path is invalid or outside the allowed storage directories.';
$strings['ErrorMissingDeletedMarker'] = 'The file is not marked with the DELETED marker.';
$strings['ErrorReferencedPath'] = 'The file is still referenced by Chamilo metadata and cannot be deleted here.';
$strings['ErrorNotCleanablePath'] = 'Het bestand is geen opruimbare orphan of legacy verwijderd bestand.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Opruimbare fysieke bestanden';
$strings['NoCleanableFiles'] = 'Er zijn geen opruimbare fysieke bestanden in deze opslagroot';
$strings['Chamilo2ResourceStorage'] = 'Bronbestanden';
$strings['Chamilo2ResourceStorageHelp'] = 'Bestanden onder var/upload/resource worden alleen getoond wanneer ze niet worden verwezen door resource_file. Verwijder documenten via de Documenten-tool of API, niet via deze plugin.';
$strings['Chamilo2AssetStorage'] = 'Assetbestanden';
$strings['Chamilo2AssetStorageHelp'] = 'Bestanden onder var/upload/assets worden alleen getoond wanneer ze niet worden verwezen door asset. Gerefereerde assets zijn altijd beschermd.';
$strings['LegacyCourseFiles'] = 'Legacy cursusbestanden';
$strings['LegacyUploadFiles'] = 'Legacy uploadbestanden';
$strings['LegacyPublicCourseFiles'] = 'Legacy openbare cursusbestanden';
$strings['LegacyPublicUploadFiles'] = 'Legacy openbare uploadbestanden';
$strings['LegacyDeletedStorageHelp'] = 'Legacy roots worden alleen gescand op bestanden waarvan de basisnaam de DELETED-marker bevat.';
$strings['OrphanResourceFile'] = 'Orphan bronbestand';
$strings['OrphanAssetFile'] = 'Orphan assetbestand';
$strings['LegacyDeletedFile'] = 'Legacy DELETED-bestand';
$strings['OrphanResourceFiles'] = 'Orphan bronbestanden';
$strings['OrphanAssetFiles'] = 'Orphan assetbestanden';
$strings['LegacyDeletedFiles'] = 'Legacy DELETED-bestanden';
$strings['Reason'] = 'Reden';
$strings['ReasonOrphanResource'] = 'Fysiek bestand bestaat in resource-opslag maar geen resource_file-rij verwijst ernaar.';
$strings['ReasonOrphanAsset'] = 'Fysiek bestand bestaat in asset-opslag maar geen asset-rij verwijst ernaar.';
$strings['ReasonLegacyDeletedMarker'] = 'Basisnaam van legacy bestand bevat de DELETED-marker.';

$strings['StorageNoticeShort'] = 'Geüploade bestanden worden bijgehouden via resource_file en asset-metadata. Deze plugin toont alleen fysieke bestanden onder var/upload/resource en var/upload/assets die niet langer worden verwezen.';
$strings['SafeNoticeShort'] = 'Een bestand met een geldige database-referentie is beschermd. Documenten en bestanden moeten nog steeds worden verwijderd via hun normale tools.';
$strings['CheckedLocations'] = 'Gecontroleerde locaties';
$strings['DetectionRule'] = 'Detectieregel';
$strings['NoCleanableFilesFound'] = 'Geen opruimbare fysieke bestanden gevonden';
$strings['NoCleanableFilesFoundHelp'] = 'Dit is het verwachte resultaat wanneer de opslag consistent is. De gecontroleerde locaties worden hieronder getoond voor transparantie.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Beperkte scan uitvoeren';
$strings['ScanNotRun'] = 'Scan niet gestart';
$strings['ScanNotRunHelp'] = 'De pagina scant de opslag niet automatisch omdat grote var/upload-mappen traag kunnen zijn. Klik op Beperkte scan uitvoeren om lokale orphan-bestanden te inspecteren.';
$strings['ScanLimitedWarning'] = 'De scan is vroegtijdig gestopt om de pagina responsief te houden. Voer hem later opnieuw uit of inspecteer de opslag via de command line indien nodig.';

$strings['PathFilter'] = 'Padfilter';
$strings['PathFilterHelp'] = 'Optioneel. Gebruik dit om een specifieke map te testen of te bekijken, bijvoorbeeld clean-deleted-files-test. Het matcht alleen relatieve paden.';
$strings['ActivePathFilter'] = 'Actief padfilter: %s';
