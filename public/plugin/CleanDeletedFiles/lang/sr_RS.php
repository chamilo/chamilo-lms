<?php
$strings['plugin_title'] = 'Očisti obrisane datoteke';
$strings['plugin_comment'] = 'Trajno obriši datoteke označene kao obrisane. Omogućite ga u oblasti menu_administrator, zatim pristupite sa glavne admin stranice.';
$strings['FileList'] = 'Lista datoteka označenih kao obrisane';
$strings['SizeTotalAllDir'] = 'Ukupna veličina (svi direktorijumi)';
$strings['NoFilesDeleted'] = 'Nema datoteka označenih kao obrisane';
$strings['FilesDeletedMark'] = 'Datoteke označene kao obrisane';
$strings['FileDirSize'] = 'Veličina datoteka direktorijuma';
$strings['ConfirmDelete'] = 'Da li ste sigurni da želite da obrišete datoteku?';
$strings['ErrorDeleteFile'] = 'Došlo je do greške prilikom brisanja datoteke';
$strings['ErrorEmptyPath'] = 'Došlo je do problema prilikom brisanja datoteke, putanja ne može biti prazna';
$strings['DeleteSelectedFiles'] = 'Obriši izabrane datoteke';
$strings['ConfirmDeleteFiles'] = 'Da li ste sigurni da želite da obrišete sve izabrane datoteke?';
$strings['DeletedSuccess'] = 'Brisanje datoteke je uspešno završeno';
$strings['path_dir'] = 'Direktorijum';
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
$strings['ErrorNotCleanablePath'] = 'Fajl nije čistiv osiroteli ili zastareli obrisani fajl.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Čistivi fizički fajlovi';
$strings['NoCleanableFiles'] = 'Nema čistivih fizičkih fajlova u ovom korenu skladišta';
$strings['Chamilo2ResourceStorage'] = 'Fajlovi resursa';
$strings['Chamilo2ResourceStorageHelp'] = 'Fajlovi ispod var/upload/resource se prikazuju samo kada nisu referencirani preko resource_file. Brišite dokumente iz alatke Dokumenti ili API-jem, a ne iz ovog dodatka.';
$strings['Chamilo2AssetStorage'] = 'Fajlovi sredstava';
$strings['Chamilo2AssetStorageHelp'] = 'Fajlovi ispod var/upload/assets se prikazuju samo kada nisu referencirani preko asset. Referencirana sredstva su uvek zaštićena.';
$strings['LegacyCourseFiles'] = 'Zastareli fajlovi kurseva';
$strings['LegacyUploadFiles'] = 'Zastareli fajlovi za otpremanje';
$strings['LegacyPublicCourseFiles'] = 'Zastareli javni fajlovi kurseva';
$strings['LegacyPublicUploadFiles'] = 'Zastareli javni fajlovi za otpremanje';
$strings['LegacyDeletedStorageHelp'] = 'Zastareli koreni se skeniraju samo za fajlove čiji bazni naziv sadrži marker DELETED.';
$strings['OrphanResourceFile'] = 'Osiroteli fajl resursa';
$strings['OrphanAssetFile'] = 'Osiroteli fajl sredstva';
$strings['LegacyDeletedFile'] = 'Zastareli DELETED fajl';
$strings['OrphanResourceFiles'] = 'Osiroteli fajlovi resursa';
$strings['OrphanAssetFiles'] = 'Osiroteli fajlovi sredstava';
$strings['LegacyDeletedFiles'] = 'Zastareli DELETED fajlovi';
$strings['Reason'] = 'Razlog';
$strings['ReasonOrphanResource'] = 'Fizički fajl postoji u skladištu resursa, ali nijedan red u resource_file ne pokazuje na njega.';
$strings['ReasonOrphanAsset'] = 'Fizički fajl postoji u skladištu sredstava, ali nijedan red u asset ne pokazuje na njega.';
$strings['ReasonLegacyDeletedMarker'] = 'Bazni naziv zastarelog fajla sadrži marker DELETED.';

$strings['StorageNoticeShort'] = 'Otpremljeni fajlovi se prate preko metapodataka resource_file i asset. Ovaj dodatak prikazuje samo fizičke fajlove ispod var/upload/resource i var/upload/assets koji više nisu referencirani.';
$strings['SafeNoticeShort'] = 'Fajl sa važećom referencom u bazi podataka je zaštićen. Dokumente i fajlove treba i dalje brisati preko njihovih uobičajenih alatki.';
$strings['CheckedLocations'] = 'Proverene lokacije';
$strings['DetectionRule'] = 'Pravilo detekcije';
$strings['NoCleanableFilesFound'] = 'Nisu pronađeni čistivi fizički fajlovi';
$strings['NoCleanableFilesFoundHelp'] = 'Ovo je očekivani rezultat kada je skladište konzistentno. Proverene lokacije su prikazane ispod radi transparentnosti.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Pokreni ograničeno skeniranje';
$strings['ScanNotRun'] = 'Skeniranje nije započeto';
$strings['ScanNotRunHelp'] = 'Stranica ne skenira skladište automatski jer veliki var/upload folderi mogu biti spori. Kliknite na Pokreni ograničeno skeniranje da pregledate lokalne osirotele fajlove.';
$strings['ScanLimitedWarning'] = 'Skeniranje je rano zaustavljeno da bi stranica ostala responzivna. Pokrenite ga ponovo kasnije ili pregledajte skladište iz komandne linije ako je potrebno.';

$strings['PathFilter'] = 'Filter putanje';
$strings['PathFilterHelp'] = 'Opciono. Koristite ovo da testirate ili pregledate određeni folder, na primer clean-deleted-files-test. Podudara se samo sa relativnim putanjama.';
$strings['ActivePathFilter'] = 'Aktivni filter putanje: %s';
