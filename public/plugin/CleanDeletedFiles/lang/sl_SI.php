<?php
$strings['plugin_title'] = 'Počisti izbrisane datoteke';
$strings['plugin_comment'] = 'Trajno izbriši datoteke označene kot izbrisane. Omogočite jo v območju menu_administrator, nato pa dostopajte do nje s glavne administratorske strani.';
$strings['FileList'] = 'Seznam datotek označenih kot izbrisane';
$strings['SizeTotalAllDir'] = 'Skupna velikost (vse mape)';
$strings['NoFilesDeleted'] = 'Ni datotek označenih kot izbrisane';
$strings['FilesDeletedMark'] = 'Datoteke označene kot izbrisane';
$strings['FileDirSize'] = 'Velikost datotek v mapi';
$strings['ConfirmDelete'] = 'Ste prepričani, da želite izbrisati datoteko?';
$strings['ErrorDeleteFile'] = 'Prišlo je do napake med brisanjem datoteke';
$strings['ErrorEmptyPath'] = 'Prišlo je do težave pri brisanju datoteke, pot ne more biti prazna';
$strings['DeleteSelectedFiles'] = 'Izbriši izbrane datoteke';
$strings['ConfirmDeleteFiles'] = 'Ste prepričani, da želite izbrisati vse izbrane datoteke?';
$strings['DeletedSuccess'] = 'Brisanje datoteke je bilo uspešno';
$strings['path_dir'] = 'Mapa';
$strings['size'] = 'Velikost';
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
$strings['ErrorNotCleanablePath'] = 'Datoteka ni čistljiva osirotela ali opuščena izbrisana datoteka.';
$strings['DeletedFilesCount'] = 'Deleted files';
$strings['SkippedFilesCount'] = 'Skipped files';
$strings['NoSelection'] = 'No files were selected.';
$strings['CleanableFiles'] = 'Čistljive fizične datoteke';
$strings['NoCleanableFiles'] = 'V tem korenskem direktoriju shrambe ni čistljivih fizičnih datotek';
$strings['Chamilo2ResourceStorage'] = 'Datoteke virov';
$strings['Chamilo2ResourceStorageHelp'] = 'Datoteke v var/upload/resource so prikazane samo, če nanje ne kaže nobena vrstica resource_file. Dokumente brišite iz orodja Dokumenti ali prek API, ne iz tega vtičnika.';
$strings['Chamilo2AssetStorage'] = 'Datoteke sredstev';
$strings['Chamilo2AssetStorageHelp'] = 'Datoteke v var/upload/assets so prikazane samo, če nanje ne kaže nobena vrstica asset. Sklicana sredstva so vedno zaščitena.';
$strings['LegacyCourseFiles'] = 'Opuščene datoteke predmetov';
$strings['LegacyUploadFiles'] = 'Opuščene naložene datoteke';
$strings['LegacyPublicCourseFiles'] = 'Opuščene javne datoteke predmetov';
$strings['LegacyPublicUploadFiles'] = 'Opuščene javne naložene datoteke';
$strings['LegacyDeletedStorageHelp'] = 'Opuščeni korenski direktoriji se pregledujejo samo za datoteke, katerih osnovno ime vsebuje oznako DELETED.';
$strings['OrphanResourceFile'] = 'Osirotela datoteka vira';
$strings['OrphanAssetFile'] = 'Osirotela datoteka sredstva';
$strings['LegacyDeletedFile'] = 'Opuščena datoteka DELETED';
$strings['OrphanResourceFiles'] = 'Osirotele datoteke virov';
$strings['OrphanAssetFiles'] = 'Osirotele datoteke sredstev';
$strings['LegacyDeletedFiles'] = 'Opuščene datoteke DELETED';
$strings['Reason'] = 'Razlog';
$strings['ReasonOrphanResource'] = 'Fizična datoteka obstaja v shrambi virov, vendar nanjo ne kaže nobena vrstica resource_file.';
$strings['ReasonOrphanAsset'] = 'Fizična datoteka obstaja v shrambi sredstev, vendar nanjo ne kaže nobena vrstica asset.';
$strings['ReasonLegacyDeletedMarker'] = 'Osnovno ime opuščene datoteke vsebuje oznako DELETED.';

$strings['StorageNoticeShort'] = 'Naložene datoteke se spremljajo prek metapodatkov resource_file in asset. Ta vtičnik prikazuje samo fizične datoteke v var/upload/resource in var/upload/assets, ki niso več sklicane.';
$strings['SafeNoticeShort'] = 'Datoteka z veljavnim sklicem v podatkovni bazi je zaščitena. Dokumente in datoteke je treba še vedno brisati z njihovimi običajnimi orodji.';
$strings['CheckedLocations'] = 'Preverjene lokacije';
$strings['DetectionRule'] = 'Pravilo zaznavanja';
$strings['NoCleanableFilesFound'] = 'Ni najdenih čistljivih fizičnih datotek';
$strings['NoCleanableFilesFoundHelp'] = 'To je pričakovan rezultat, ko je shramba konsistentna. Preverjene lokacije so prikazane spodaj zaradi preglednosti.';

$strings['ResourceFiles'] = 'Resource files';

$strings['ResourceStorageHelp'] = 'Files under var/upload/resource are listed only when they are not referenced by resource_file. Delete documents from the Documents tool or API, not from this plugin.';

$strings['AssetFiles'] = 'Asset files';

$strings['AssetStorageHelp'] = 'Files under var/upload/assets are listed only when they are not referenced by asset. Referenced assets are always protected.';

$strings['RunLimitedScan'] = 'Zaženi omejen pregled';
$strings['ScanNotRun'] = 'Pregled ni začet';
$strings['ScanNotRunHelp'] = 'Stran ne pregleda shrambe samodejno, ker so velike mape var/upload lahko počasne. Kliknite Zaženi omejen pregled za pregled lokalnih osirotelih datotek.';
$strings['ScanLimitedWarning'] = 'Pregled je bil prezgodaj ustavljen, da bi ohranili odzivnost strani. Zaženite ga znova kasneje ali preglejte shrambo iz ukazne vrstice, če je potrebno.';

$strings['PathFilter'] = 'Filter poti';
$strings['PathFilterHelp'] = 'Izbirno. Uporabite za testiranje ali pregled določene mape, na primer clean-deleted-files-test. Ujema samo relativne poti.';
$strings['ActivePathFilter'] = 'Aktivni filter poti: %s';
