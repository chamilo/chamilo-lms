<?php
$strings['plugin_title'] = 'Ryd op i slettede filer';
$strings['plugin_comment'] = 'Slet permanent filer markeret som slettet. Aktivér det i menu_administrator-området, og tilgå det fra hovedadministrator-siden.';
$strings['FileList'] = 'Liste over filer markeret som slettet';
$strings['SizeTotalAllDir'] = 'Samlet størrelse (alle mapper)';
$strings['NoFilesDeleted'] = 'Der er ingen filer markeret som slettet';
$strings['FilesDeletedMark'] = 'Filer markeret som slettet';
$strings['FileDirSize'] = 'Mappefilers størrelse';
$strings['ConfirmDelete'] = 'Er du sikker på, at du vil slette filen?';
$strings['ErrorDeleteFile'] = 'Der opstod en fejl under sletning af filen';
$strings['ErrorEmptyPath'] = 'Der var et problem med at slette filen, stien må ikke være tom';
$strings['DeleteSelectedFiles'] = 'Slet valgte filer';
$strings['ConfirmDeleteFiles'] = 'Er du sikker på, at du vil slette alle de valgte filer?';
$strings['DeletedSuccess'] = 'Filsletningen var vellykket';
$strings['path_dir'] = 'Mappe';
$strings['size'] = 'Størrelse';
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
$strings['ErrorNotCleanablePath'] = 'Filen er ikke en oprydningsbar forældreløs fil eller en legacy slettet fil.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Oprydningsbare fysiske filer';
$strings['NoCleanableFiles'] = 'Der er ingen oprydningsbare fysiske filer i denne lagerrod';
$strings['Chamilo2ResourceStorage'] = 'Ressourcefiler';
$strings['Chamilo2ResourceStorageHelp'] = "Filer under var/upload/resource vises kun, når de ikke refereres af resource_file. Slet dokumenter fra Dokumentværktøjet eller API'et, ikke fra dette plugin.";
$strings['Chamilo2AssetStorage'] = 'Asset-filer';
$strings['Chamilo2AssetStorageHelp'] = 'Filer under var/upload/assets vises kun, når de ikke refereres af asset. Refererede assets er altid beskyttede.';
$strings['LegacyCourseFiles'] = 'Legacy kursusfiler';
$strings['LegacyUploadFiles'] = 'Legacy upload-filer';
$strings['LegacyPublicCourseFiles'] = 'Legacy offentlige kursusfiler';
$strings['LegacyPublicUploadFiles'] = 'Legacy offentlige upload-filer';
$strings['LegacyDeletedStorageHelp'] = 'Legacy-rods mapper scannes kun for filer, hvis basisfilnavn indeholder DELETED-markøren.';
$strings['OrphanResourceFile'] = 'Forældreløs ressourcefil';
$strings['OrphanAssetFile'] = 'Forældreløs asset-fil';
$strings['LegacyDeletedFile'] = 'Legacy DELETED-fil';
$strings['OrphanResourceFiles'] = 'Forældreløse ressourcefiler';
$strings['OrphanAssetFiles'] = 'Forældreløse asset-filer';
$strings['LegacyDeletedFiles'] = 'Legacy DELETED-filer';
$strings['Reason'] = 'Årsag';
$strings['ReasonOrphanResource'] = 'Fysisk fil findes i ressource-lager, men ingen resource_file-række peger på den.';
$strings['ReasonOrphanAsset'] = 'Fysisk fil findes i asset-lager, men ingen asset-række peger på den.';
$strings['ReasonLegacyDeletedMarker'] = 'Legacy-filens basisfilnavn indeholder DELETED-markøren.';

$strings['StorageNoticeShort'] = 'Uploadede filer spores gennem resource_file- og asset-metadata. Dette plugin viser kun fysiske filer under var/upload/resource og var/upload/assets, som ikke længere refereres.';
$strings['SafeNoticeShort'] = 'En fil med en gyldig database-reference er beskyttet. Dokumenter og filer bør stadig slettes gennem deres normale værktøjer.';
$strings['CheckedLocations'] = 'Kontrollerede placeringer';
$strings['DetectionRule'] = 'Detektionsregel';
$strings['NoCleanableFilesFound'] = 'Ingen oprydningsbare fysiske filer fundet';
$strings['NoCleanableFilesFoundHelp'] = 'Dette er det forventede resultat, når lageret er konsistent. De kontrollerede placeringer vises nedenfor for gennemsigtighed.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Kør begrænset scanning';
$strings['ScanNotRun'] = 'Scanning ikke startet';
$strings['ScanNotRunHelp'] = 'Siden scanner ikke lageret automatisk, da store var/upload-mapper kan være langsomme. Klik på Kør begrænset scanning for at undersøge lokale forældreløse filer.';
$strings['ScanLimitedWarning'] = 'Scanningen blev stoppet tidligt for at holde siden responsiv. Kør den igen senere eller undersøg lageret fra kommandolinjen om nødvendigt.';

$strings['PathFilter'] = 'Stifilter';
$strings['PathFilterHelp'] = 'Valgfrit. Brug dette til at teste eller gennemgå en specifik mappe, f.eks. clean-deleted-files-test. Det matcher kun relative stier.';
$strings['ActivePathFilter'] = 'Aktivt stifilter: %s';
