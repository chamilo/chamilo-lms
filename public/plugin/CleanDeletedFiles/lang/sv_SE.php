<?php
$strings['plugin_title'] = 'Rensa borttagna filer';
$strings['plugin_comment'] = 'Ta permanent bort filer markerade som borttagna. Aktivera det i menyn för administratör och få åtkomst från huvudadministrationssidan.';
$strings['FileList'] = 'Lista över filer markerade som borttagna';
$strings['SizeTotalAllDir'] = 'Total storlek (alla kataloger)';
$strings['NoFilesDeleted'] = 'Det finns inga filer markerade som borttagna';
$strings['FilesDeletedMark'] = 'Filer markerade som borttagna';
$strings['FileDirSize'] = 'Katalogfilsstorlek';
$strings['ConfirmDelete'] = 'Är du säker på att du vill ta bort filen?';
$strings['ErrorDeleteFile'] = 'Ett fel uppstod vid borttagning av filen';
$strings['ErrorEmptyPath'] = 'Det uppstod ett problem vid borttagning av filen, sökvägen får inte vara tom';
$strings['DeleteSelectedFiles'] = 'Ta bort valda filer';
$strings['ConfirmDeleteFiles'] = 'Är du säker på att du vill ta bort alla valda filer?';
$strings['DeletedSuccess'] = 'Filborttagningen lyckades';
$strings['path_dir'] = 'Katalog';
$strings['size'] = 'Storlek';
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
$strings['ErrorNotCleanablePath'] = 'Filen är inte en rensningsbar föräldralös fil eller en föråldrad borttagen fil.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Rensningsbara fysiska filer';
$strings['NoCleanableFiles'] = 'Det finns inga rensningsbara fysiska filer i denna lagringsrot';
$strings['Chamilo2ResourceStorage'] = 'Resursfiler';
$strings['Chamilo2ResourceStorageHelp'] = 'Filer under var/upload/resource listas endast när de inte refereras av resource_file. Ta bort dokument från Dokumentverktyget eller API, inte från denna plugin.';
$strings['Chamilo2AssetStorage'] = 'Tillgångsfiler';
$strings['Chamilo2AssetStorageHelp'] = 'Filer under var/upload/assets listas endast när de inte refereras av asset. Refererade tillgångar är alltid skyddade.';
$strings['LegacyCourseFiles'] = 'Föråldrade kursfiler';
$strings['LegacyUploadFiles'] = 'Föråldrade uppladdningsfiler';
$strings['LegacyPublicCourseFiles'] = 'Föråldrade publika kursfiler';
$strings['LegacyPublicUploadFiles'] = 'Föråldrade publika uppladdningsfiler';
$strings['LegacyDeletedStorageHelp'] = 'Föråldrade rotmappar genomsöks endast efter filer vars basnamn innehåller DELETED-markören.';
$strings['OrphanResourceFile'] = 'Föräldralös resursfil';
$strings['OrphanAssetFile'] = 'Föräldralös tillgångsfil';
$strings['LegacyDeletedFile'] = 'Föråldrad DELETED-fil';
$strings['OrphanResourceFiles'] = 'Föräldralösa resursfiler';
$strings['OrphanAssetFiles'] = 'Föräldralösa tillgångsfiler';
$strings['LegacyDeletedFiles'] = 'Föråldrade DELETED-filer';
$strings['Reason'] = 'Orsak';
$strings['ReasonOrphanResource'] = 'Fysisk fil finns i resurslagring men ingen resource_file-rad pekar på den.';
$strings['ReasonOrphanAsset'] = 'Fysisk fil finns i tillgångslagring men ingen asset-rad pekar på den.';
$strings['ReasonLegacyDeletedMarker'] = 'Föråldrat filbasnamn innehåller DELETED-markören.';

$strings['StorageNoticeShort'] = 'Uppladdade filer spåras genom resource_file- och asset-metadata. Denna plugin listar endast fysiska filer under var/upload/resource och var/upload/assets som inte längre refereras.';
$strings['SafeNoticeShort'] = 'En fil med en giltig databasreferens är skyddad. Dokument och filer bör fortfarande tas bort via sina vanliga verktyg.';
$strings['CheckedLocations'] = 'Kontrollerade platser';
$strings['DetectionRule'] = 'Detekteringsregel';
$strings['NoCleanableFilesFound'] = 'Inga rensningsbara fysiska filer hittades';
$strings['NoCleanableFilesFoundHelp'] = 'Detta är det förväntade resultatet när lagringen är konsekvent. De kontrollerade platserna visas nedan för transparens.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Kör begränsad skanning';
$strings['ScanNotRun'] = 'Skanning ej startad';
$strings['ScanNotRunHelp'] = 'Sidan skannar inte lagringen automatiskt eftersom stora var/upload-mappar kan vara långsamma. Klicka på Kör begränsad skanning för att inspektera lokala föräldralösa filer.';
$strings['ScanLimitedWarning'] = 'Skanningen stoppades tidigt för att behålla sidans responsivitet. Kör den igen senare eller inspektera lagringen från kommandoraden vid behov.';

$strings['PathFilter'] = 'Sökvägsfilter';
$strings['PathFilterHelp'] = 'Valfritt. Använd detta för att testa eller granska en specifik mapp, till exempel clean-deleted-files-test. Det matchar endast relativa sökvägar.';
$strings['ActivePathFilter'] = 'Aktivt sökvägsfilter: %s';
