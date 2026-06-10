<?php
$strings['plugin_title'] = 'Očisti obrisane datoteke';
$strings['plugin_comment'] = 'Trajno obriši datoteke označene kao obrisane. Omogući ga u području menu_administrator, zatim pristupi iz glavne administratorske stranice.';
$strings['FileList'] = 'Lista datoteka označenih kao obrisane';
$strings['SizeTotalAllDir'] = 'Ukupna veličina (svi direktoriji)';
$strings['NoFilesDeleted'] = 'Nema datoteka označenih kao obrisane';
$strings['FilesDeletedMark'] = 'Datoteke označene kao obrisane';
$strings['FileDirSize'] = 'Veličina datoteka direktorija';
$strings['ConfirmDelete'] = 'Jeste li sigurni da želite obrisati datoteku?';
$strings['ErrorDeleteFile'] = 'Došlo je do greške prilikom brisanja datoteke';
$strings['ErrorEmptyPath'] = 'Došlo je do problema prilikom brisanja datoteke, putanja ne može biti prazna';
$strings['DeleteSelectedFiles'] = 'Obriši odabrane datoteke';
$strings['ConfirmDeleteFiles'] = 'Jeste li sigurni da želite obrisati sve odabrane datoteke?';
$strings['DeletedSuccess'] = 'Brisanje datoteke je uspješno';
$strings['path_dir'] = 'Direktorij';
$strings['size'] = 'Veličina';
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
$strings['ErrorNotCleanablePath'] = 'Datoteka nije datoteka za čišćenje ili naslijeđena obrisana datoteka.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Datoteke za čišćenje';
$strings['NoCleanableFiles'] = 'Nema datoteka za čišćenje u ovom korijenu pohrane';
$strings['Chamilo2ResourceStorage'] = 'Datoteke resursa';
$strings['Chamilo2ResourceStorageHelp'] = 'Datoteke u var/upload/resource su navedene samo kada nisu referencirane preko resource_file. Brišite dokumente iz alata Dokumenti ili API-ja, a ne iz ovog dodatka.';
$strings['Chamilo2AssetStorage'] = 'Datoteke sredstava';
$strings['Chamilo2AssetStorageHelp'] = 'Datoteke u var/upload/assets su navedene samo kada nisu referencirane preko asset. Referencirana sredstva su uvijek zaštićena.';
$strings['LegacyCourseFiles'] = 'Naslijeđene datoteke kursa';
$strings['LegacyUploadFiles'] = 'Naslijeđene datoteke za upload';
$strings['LegacyPublicCourseFiles'] = 'Naslijeđene javne datoteke kursa';
$strings['LegacyPublicUploadFiles'] = 'Naslijeđene javne datoteke za upload';
$strings['LegacyDeletedStorageHelp'] = 'Naslijeđeni korijeni se pregledavaju samo za datoteke čiji naziv sadrži oznaku DELETED.';
$strings['OrphanResourceFile'] = 'Napuštena datoteka resursa';
$strings['OrphanAssetFile'] = 'Napuštena datoteka sredstva';
$strings['LegacyDeletedFile'] = 'Naslijeđena DELETED datoteka';
$strings['OrphanResourceFiles'] = 'Napuštene datoteke resursa';
$strings['OrphanAssetFiles'] = 'Napuštene datoteke sredstava';
$strings['LegacyDeletedFiles'] = 'Naslijeđene DELETED datoteke';
$strings['Reason'] = 'Razlog';
$strings['ReasonOrphanResource'] = 'Fizička datoteka postoji u pohrani resursa, ali nijedan red resource_file ne pokazuje na nju.';
$strings['ReasonOrphanAsset'] = 'Fizička datoteka postoji u pohrani sredstava, ali nijedan red asset ne pokazuje na nju.';
$strings['ReasonLegacyDeletedMarker'] = 'Naziv naslijeđene datoteke sadrži oznaku DELETED.';

$strings['StorageNoticeShort'] = 'Uploadovane datoteke se prate preko metapodataka resource_file i asset. Ovaj dodatak navodi samo fizičke datoteke u var/upload/resource i var/upload/assets koje više nisu referencirane.';
$strings['SafeNoticeShort'] = 'Datoteka sa važećom referencom u bazi podataka je zaštićena. Dokumenti i datoteke se i dalje trebaju brisati preko njihovih uobičajenih alata.';
$strings['CheckedLocations'] = 'Provjerene lokacije';
$strings['DetectionRule'] = 'Pravilo detekcije';
$strings['NoCleanableFilesFound'] = 'Nisu pronađene datoteke za čišćenje';
$strings['NoCleanableFilesFoundHelp'] = 'Ovo je očekivani rezultat kada je pohrana konzistentna. Provjerene lokacije su prikazane ispod radi transparentnosti.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Pokreni ograničeno skeniranje';
$strings['ScanNotRun'] = 'Skeniranje nije započeto';
$strings['ScanNotRunHelp'] = 'Stranica ne skenira pohranu automatski jer velike var/upload fascikle mogu biti spore. Kliknite Pokreni ograničeno skeniranje da pregledate lokalne napuštene datoteke.';
$strings['ScanLimitedWarning'] = 'Skeniranje je zaustavljeno ranije kako bi stranica ostala responzivna. Pokrenite ga ponovo kasnije ili pregledajte pohranu iz komandne linije ako je potrebno.';

$strings['PathFilter'] = 'Filter putanje';
$strings['PathFilterHelp'] = 'Opcionalno. Koristite ovo za testiranje ili pregled određene fascikle, na primjer clean-deleted-files-test. Podudara se samo sa relativnim putanjama.';
$strings['ActivePathFilter'] = 'Aktivni filter putanje: %s';
