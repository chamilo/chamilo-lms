<?php
$strings['plugin_title'] = 'Ryd opp sletta filer';
$strings['plugin_comment'] = 'Slett permanent filer merka som sletta. Aktiver det i menya_administrator-regionen, deretter få tilgang frå hovudsida for administrator.';
$strings['FileList'] = 'Liste over filer merka som sletta';
$strings['SizeTotalAllDir'] = 'Total storleik (alle katalogar)';
$strings['NoFilesDeleted'] = 'Det finst ingen filer merka som sletta';
$strings['FilesDeletedMark'] = 'Filer merka som sletta';
$strings['FileDirSize'] = 'Katalogfiler sin storleik';
$strings['ConfirmDelete'] = 'Er du sikker på at du vil slette fila?';
$strings['ErrorDeleteFile'] = 'Ein feil oppstod under sletting av fila';
$strings['ErrorEmptyPath'] = 'Det var eit problem med å slette fila, stien kan ikkje vere tom';
$strings['DeleteSelectedFiles'] = 'Slett valde filer';
$strings['ConfirmDeleteFiles'] = 'Er du sikker på at du vil slette alle valde filer?';
$strings['DeletedSuccess'] = 'Sletting av fila var vellykta';
$strings['path_dir'] = 'Katalog';
$strings['size'] = 'Storleik';
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
$strings['ErrorNotCleanablePath'] = 'Fila er ikkje ein reinbar foreldrelaus fil eller ein legacy-sletta fil.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Reinbare fysiske filer';
$strings['NoCleanableFiles'] = 'Det finst ingen reinbare fysiske filer i denne lagringsrota';
$strings['Chamilo2ResourceStorage'] = 'Ressursfiler';
$strings['Chamilo2ResourceStorageHelp'] = 'Filer under var/upload/resource vert lista berre når dei ikkje er referert til av resource_file. Slett dokument frå dokumentverktøyet eller API-en, ikkje frå denne plugin-en.';
$strings['Chamilo2AssetStorage'] = 'Asset-filer';
$strings['Chamilo2AssetStorageHelp'] = 'Filer under var/upload/assets vert lista berre når dei ikkje er referert til av asset. Refererte assets er alltid verna.';
$strings['LegacyCourseFiles'] = 'Legacy-kursfiler';
$strings['LegacyUploadFiles'] = 'Legacy-opplastingsfiler';
$strings['LegacyPublicCourseFiles'] = 'Legacy offentlege kursfiler';
$strings['LegacyPublicUploadFiles'] = 'Legacy offentlege opplastingsfiler';
$strings['LegacyDeletedStorageHelp'] = 'Legacy-roter vert skanna berre for filer der basenamnet inneheld DELETED-markøren.';
$strings['OrphanResourceFile'] = 'Foreldrelaus ressursfil';
$strings['OrphanAssetFile'] = 'Foreldrelaus asset-fil';
$strings['LegacyDeletedFile'] = 'Legacy DELETED-fil';
$strings['OrphanResourceFiles'] = 'Foreldrelause ressursfiler';
$strings['OrphanAssetFiles'] = 'Foreldrelause asset-filer';
$strings['LegacyDeletedFiles'] = 'Legacy DELETED-filer';
$strings['Reason'] = 'Årsak';
$strings['ReasonOrphanResource'] = 'Fysisk fil finst i ressurslagring, men ingen resource_file-rad peikar til henne.';
$strings['ReasonOrphanAsset'] = 'Fysisk fil finst i asset-lagring, men ingen asset-rad peikar til henne.';
$strings['ReasonLegacyDeletedMarker'] = 'Basenamn på legacy-fil inneheld DELETED-markøren.';

$strings['StorageNoticeShort'] = 'Opplasta filer vert spora gjennom resource_file- og asset-metadata. Denne plugin-en listar berre fysiske filer under var/upload/resource og var/upload/assets som ikkje lenger er referert til.';
$strings['SafeNoticeShort'] = 'Ei fil med gyldig databasetilknyting er verna. Dokument og filer bør framleis slettast gjennom dei vanlege verktøya.';
$strings['CheckedLocations'] = 'Kontrollerte plasseringar';
$strings['DetectionRule'] = 'Oppdagingsregel';
$strings['NoCleanableFilesFound'] = 'Ingen reinbare fysiske filer funne';
$strings['NoCleanableFilesFoundHelp'] = 'Dette er det venta resultatet når lagringa er konsistent. Dei kontrollerte plasseringane er viste nedanfor for openheit.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Køyr avgrensa skanning';
$strings['ScanNotRun'] = 'Skanning ikkje starta';
$strings['ScanNotRunHelp'] = 'Sida skannar ikkje lagringa automatisk fordi store var/upload-mapper kan vere trege. Klikk på Køyr avgrensa skanning for å undersøkje lokale foreldrelause filer.';
$strings['ScanLimitedWarning'] = 'Skanninga vart stoppa tidleg for å halde sida responsiv. Køyr ho på nytt seinare eller undersøk lagringa frå kommandolinja om nødvendig.';

$strings['PathFilter'] = 'Stifilter';
$strings['PathFilterHelp'] = 'Valfritt. Bruk dette for å teste eller gå gjennom ei spesifikk mappe, til dømes clean-deleted-files-test. Det treffer berre relative stiar.';
$strings['ActivePathFilter'] = 'Aktivt stifilter: %s';
